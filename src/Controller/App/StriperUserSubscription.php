<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/23/17
 * Time: 7:22 PM
 */

namespace Drupal\striper\Controller\App;


use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\striper\StriperStripeAPI;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class StriperUserSubscription extends ControllerBase {

    private $stripe;

    public function __construct() {
        $this->stripe = new StriperStripeAPI();
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    public function viewSubscriptions(Request $request) {
        // get subscriptions from drupal
        // if subscription, get from Stripe
            // display a cancel button

        $user_subscription = \Drupal::database()->query("SELECT s.plan_end " .
                                                    "FROM {striper_subscriptions} s where s.uid = {:uid}",
                                                    array(':uid' => $this->currentUser()->id()))->fetchObject();

        if($user_subscription) {
            $subscription = $this->t('%name, your subscription ends on %date',
                                     array('%name' => $this->currentUser()->getDisplayName(),
                                         '%date' => \Drupal::service('date.formatter')->format($user_subscription->plan_end),
            ));
            $link = render(Link::createFromRoute($this->t('Cancel Subscription'),
                                                 'striper.app.user.subscriptions.cancel',
                                                 array('user' => $this->currentUser()->id()))->toRenderable());
        } else {
            $subscription = $this->t('@name, you haven\'t signed up for a subscription',
                                     array('@name' => $this->currentUser()->getDisplayName(),));
            $link = '';
        }

        return array(
            '#theme' => 'striper_subscriptions',
            '#link' => $link,
            '#subscription' => $subscription,
        );
    }

    public function cancel(Request $request) {
        drupal_set_message($this->t("Successfully cancelled subscription"));

        $stripe_sid = \Drupal::database()->query("SELECT s.stripe_sid " .
                                                        "FROM {striper_subscriptions} s where s.uid = {:uid}",
                                                        array(':uid' => $this->currentUser()->id()))->fetchObject();
        $subscription = \Stripe\Subscription::retrieve($stripe_sid->stripe_sid);
        $subscription->cancel(array('at_period_end' => TRUE));

        \Drupal::database()->query("UPDATE {striper_subscriptions} s SET s.status = {:status} WHERE s.uid = {:uid}",
            array(':uid' => $this->currentUser()->id(), ':status' => 'cancelled'));

        return $this->redirect('striper.app.user.subscriptions', array('user' => $this->currentUser()->id()));
    }
}