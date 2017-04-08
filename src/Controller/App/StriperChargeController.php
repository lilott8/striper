<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/21/17
 * Time: 11:43 AM
 */

namespace Drupal\striper\Controller\App;

use Drupal\striper;
use Drupal\Core\Controller\ControllerBase;
use Masterminds\HTML5\Exception;
use Stripe\Charge;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;


class StriperChargeController extends ControllerBase {

    /**
     * Drupal\stripe_api\StripeApiService definition.
     *
     * @var \Drupal\striper\StriperStripeAPI
     */
    protected $striperApi;
    protected $db;

    /**
     * {@inheritdoc}
     */
    public function __construct() {
        $this->striperApi = new striper\StriperStripeAPI();
        $this->db = \Drupal::database();
    }

    /**
     * 1) See if the user has a subscription
     * 2) if we don't have them in stripe, add them
     * 3) If they have a subscription and not the same, update
     * 4) if it is the same and still subscribed, do nothing
     *
     * @throws Exception
     *  sdfaf
     *
     * @return string
     *   Return Hello string.
     */
    public function charge(Request $request) {
        // load the user
        $user = \Drupal::currentUser()->getAccount();
        // get the striper config from the entityconfig store
        $config = \Drupal::configFactory()->get('striper.striper_plan.'.$request->get('plan_name'));
        // Load the price from the config that was purchased.
        $amount = $config->get('plan_price');
        // token from stripe
        $token = $request->get('stripeToken');

        if (!$token || (is_null($config) || empty($config))) {
            throw new \Exception("Required data is missing!");
        }
        $message = t("You've successfully subscribed to plan %plan",
                     array('%array' => $config->get('plan_name')));
        $status = 'status';

        // load the striper object from the database
        $striper_user = $this->db->query("SELECT * FROM {striper_subscriptions} s WHERE s.uid = :id",
                                         array(':id' => $user->id()))->fetchObject();

        try {
            // If we don't have a user, create them
            if(is_null($striper_user) || empty($striper_user)) {
                // otherwise we must create the user in Stripe
                $customer = \Stripe\Customer::create(
                    array(
                        'email' => $user->getEmail(),
                        'source' => $token,
                        'metadata' => array(
                            'plan' => $request->get('id'),
                            'email' => $user->getEmail(),
                            'uid' => $user->id(),
                        )
                    )
                );
                $customer_id = $customer->id;
                $fields = array(
                    'uid' => $user->id(),
                    'stripe_cid' => $customer_id,
                );
                // save the customer id in Drupal's database
                $this->db->insert('striper_subscriptions')->fields($fields)->execute();
            } else {
                $customer_id = $striper_user->stripe_cid;
            }

            $subscription = NULL;
            // we don't have a user thus, we don't have a subscription
            if(is_null($striper_user) || empty($striper_user)) {
                // Create the subscription from the user
                $subscription = \Stripe\Subscription::create(
                    array(
                        "customer" => $customer_id,
                        "plan" => $config->get('id'),
                        "trial_end" => "now",
                        'metadata' => array(
                            'plan' => $config->get('id'),
                            'email' => $user->getEmail(),
                            'uid' => $user->id(),
                        )
                    )
                );
            }
            // if the plan is expired or the plan has changed
            else {
                $subscription = \Stripe\Subscription::retrieve($striper_user->stripe_sid);
                $subscription->plan = $config->get('id');
                $subscription->save();
            }

            // we have a problem with the subscription
            if(is_null($subscription) || empty($subscription)) {
                $status = 'error';
                $message = t('There was an error processing your payment.');
                \Drupal::logger('striper')->warning(striper\StriperDebug::vd($subscription));
            } else {
                $fields = array(
                    'plan' => $config->get('id'),
                    'status' => SUBSCRIPTION_STATES['active'],
                    'plan_end' => $subscription->current_period_end,
                    'stripe_sid' => $subscription->id,
                );
                $this->db->update('striper_subscriptions')->fields($fields)
                    ->where('uid = :uid', array(':uid' => $user->id()))->execute();

                \Drupal::logger('striper')->info(t("Successfully processed Stripe charge. This should have triggered a Stripe webhook"));

                // add the role to the user.
                $user = \Drupal\user\Entity\User::load($this->currentUser()->id());
                $user->addRole(\Drupal\user\Entity\Role::load('stripe_subscriber')->id());
                $user->save();

                // In addition to the webhook, we fire a traditional Drupal hook to permit other modules to respond to this event instantaneously.
                $this->moduleHandler()->invokeAll('stripe_checkout_charge_succeeded', [
                    $subscription,
                    $config,
                    $user,
                ]);
            }
            drupal_set_message($message, $status);
            //return $this->redirect('striper.app.user.subscriptions');
            return array();
        }
        catch (\Exception $e) {
            drupal_set_message(t("There was a problem processing your payment."), 'error');

            \Drupal::logger('striper')->error(t("Could not complete Stripe charge, error:\n@error\nsubmitted data:@data", [
                '@data' => $request->getContent(),
                '@error' => $e->getMessage(),
            ]));
            return $this->redirect('striper.app.user.subscriptions');
        }
    }

}