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
     * Charge.
     *
     * @return string
     *   Return Hello string.
     */
    public function charge(Request $request) {

        try {
            $token = $request->get('stripeToken');
            $config = \Drupal::configFactory()->get('striper.striper_plan.'.$request->get('plan_name'));

            if (!$token || is_null($config) || empty($config)) {
                throw new \Exception("Required data is missing!");
            }

            // Load the price from the config that was purchased.
            $amount = $config->get('plan_price');

            $user = \Drupal::currentUser()->getAccount();

            // Check to see if we have the user or not, if we do, we move on
            $customer = $this->db->query("SELECT * FROM {striper_subscriptions} s WHERE s.uid = :id",
                                         array(':id' => $user->id()))->fetchObject();

            if(is_null($customer) || empty($customer)) {
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
                $customer_id = $customer->stripe_cid;
            }

            // Create the subscription from the user
            $subscription = \Stripe\Subscription::create(
                array(
                    "customer" => $customer_id,
                    "plan" => $request->get('plan_name'),
                    "trial_end" => "now",
                    'metadata' => array(
                        'plan' => $request->get('plan_name'),
                        'email' => $user->getEmail(),
                        'uid' => $user->id(),
                    )
                )
            );

            if(is_null($subscription->id)) {
                drupal_set_message(t("There was an error in your payment."));
                return array();
            } else {
                $fields = array(
                    'plan' => $config->get('id'),
                    'status' => 'active',
                    'plan_end' => $subscription->current_period_end,
                );
                $this->db->update('striper_subscriptions')->fields($fields)
                    ->where('uid = :uid', array(':uid' => $user->id()))->execute();
            }

            //$charge = \Stripe\Charge::create(
            //    array(

            //    )
            //);

            drupal_set_message(t("Thank you. Your payment has been processed."));

            // At this point a Stripe webhook should make a request to the stripe_api.webhook route, which will dispatch an event
            // to which event subscribers can react.
            \Drupal::logger('striper')->info(t("Successfully processed Stripe charge. This should have triggered a Stripe webhook. \nsubmitted data:@data", [
                '@data' => $request->getContent(),
            ]));

            // In addition to the webhook, we fire a traditional Drupal hook to permit other modules to respond to this event instantaneously.
            $this->moduleHandler()->invokeAll('stripe_checkout_charge_succeeded', [
                $subscription,
                $config,
                $user,
            ]);

            //return $this->redirect('striper.app.user.subscriptions');
            //return array();
            //}
            /*else {
                drupal_set_message(t("Payment failed."), 'error');

                \Drupal::logger('striper')->error(t("The charge was incomplete. \nsubmitted data:@data", [
                    '@data' => $request->getContent(),
                ]));

                //return $this->redirect('striper.app.user.subscriptions');
                return array();
            }*/
            return array();
        }
        catch (\Exception $e) {
            drupal_set_message(t("Payment failed."), 'error');

            \Drupal::logger('striper')->error(t("Could not complete Stripe charge, error:\n@error\nsubmitted data:@data", [
                '@data' => $request->getContent(),
                '@error' => $e->getMessage(),
            ]));
            //return $this->redirect('striper.app.user.subscriptions');
            return array();
        }
    }

}