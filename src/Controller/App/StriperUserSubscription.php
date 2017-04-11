<?php

namespace Drupal\striper\Controller\App;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\striper\StriperDebug;
use Drupal\striper\StriperStripeAPI;
use Masterminds\HTML5\Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use \Stripe\Error\InvalidRequest;
use Stripe\Subscription;

/**
 * Class StriperUserSubscription
 *
 * @package Drupal\striper\Controller\App
 */
class StriperUserSubscription extends ControllerBase {

  private $stripe;

  /**
   * StriperUserSubscription constructor.
   */
  public function __construct() {
    $this->stripe = new StriperStripeAPI();
  }

  /**
   * @param Request $request
   *
   * @return array
   */
  public function viewSubscriptions(Request $request) {
    // Get subscriptions from drupal
    // if subscription, get from Stripe
    // display a cancel button.
    $userSubscription = \Drupal::database()->query("SELECT s.plan_end, s.status " .
                                                   "FROM {striper_subscriptions} s where s.uid = {:uid}",
                                                   array(':uid' => $this->currentUser()->id()))->fetchObject();

    if ($userSubscription) {
      $subscription = $this->t('%name, your subscription ends on %date',
                               array(
                                   '%name' => $this->currentUser()->getDisplayName(),
                                   '%date' => \Drupal::service('date.formatter')->format($userSubscription->plan_end),
                               ));
      if ($userSubscription->status == SUBSCRIPTION_STATES['active']) {
        $link = render(Link::createFromRoute($this->t('Cancel Subscription'),
                                             'striper.app.user.subscriptions.cancel',
                                             array('user' => $this->currentUser()->id()))->toRenderable());
      }
      else {
        $link = render(Link::createFromRoute($this->t('Reactivate Subscription'),
                                             'striper.app.user.subscriptions.reactivate',
                                             array('user' => $this->currentUser()->id()))->toRenderable());
      }
    }
    else {
      $subscription = $this->t("@name, you haven't signed up for a subscription",
                               array('@name' => $this->currentUser()->getDisplayName()));
      $link = '';
    }

    // TODO: figure out cache tags so I can change this more easily.
    return array(
        '#theme' => 'striper_subscriptions',
        '#link' => $link,
        '#subscription' => $subscription,
    );
  }

  public function cancel(Request $request) {
    drupal_set_message($this->t("Successfully cancelled subscription"));

    $stripeSid = \Drupal::database()->query("SELECT s.stripe_sid " .
                                            "FROM {striper_subscriptions} s where s.uid = {:uid}",
                                            array(':uid' => $this->currentUser()->id()))->fetchObject();

    \Drupal::logger('striper')->warning($stripeSid->stripe_sid);

    $subscription = Subscription::retrieve($stripeSid->stripe_sid);
    \Drupal::logger('striper')->error(StriperDebug::vd($subscription));
    $subscription->cancel(array('at_period_end' => TRUE));

    \Drupal::database()->query("UPDATE {striper_subscriptions} s SET s.status = {:status} WHERE s.uid = {:uid}",
                               array(':uid' => $this->currentUser()->id(), ':status' => SUBSCRIPTION_STATES['cancelled']));

    return $this->redirect('striper.app.user.subscriptions', array('user' => $this->currentUser()->id()));
  }

  public function reactivate(Request $request) {
    $striperSubscription = \Drupal::database()->query("SELECT * FROM {striper_subscriptions} s where s.uid = {:uid}",
                                                      array(':uid' => $this->currentUser()->id()))->fetchObject();

    $striperPlan = \Drupal::configFactory()->get('striper.striper_plan.' . $striperSubscription->plan);
    // Don't allow a user to subscribe to a plan that doesn't exist.
    if (!$striperPlan->get("plan_active")) {
      drupal_set_message(t("Plan @plan is no longer active, please subscribe to a new plan.", array("@plan" => $striperPlan->get("plan_name"))), 'warning');
      return $this->redirect('striper.app.user.subscriptions', array('user' => $this->currentUser()->id()));
    }

    try {
      $subscription = Subscription::retrieve($striperSubscription->stripe_sid);
      $subscription->plan = $striperSubscription->plan;
      $subscription->save();
      $fields = array(
          'stripe_sid' => $subscription->id,
          'status' => SUBSCRIPTION_STATES['active'],
      );
      drupal_set_message($this->t("Successfully reactivated your subscription"));
    }
    catch (InvalidRequest $e) {
      $subscription = Subscription::create(
          array(
              "customer" => $striperSubscription->stripe_cid,
              "plan" => $striperSubscription->plan,
              "trial_end" => "now",
              'metadata' => array(
                  'plan' => $striperSubscription->plan,
                  'email' => $this->currentUser()->getEmail(),
                  'uid' => $this->currentUser()->id(),
              ),
          ));
      $fields = array(
          'stripe_sid' => $subscription->id,
          'status' => SUBSCRIPTION_STATES['active'],
      );
    }

    \Drupal::database()->update('striper_subscriptions')->fields($fields)
        ->condition('uid', $this->currentUser()->id())->execute();

    return $this->redirect('striper.app.user.subscriptions', array('user' => $this->currentUser()->id()));
  }
}