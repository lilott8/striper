<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/12/17
 * Time: 10:02 PM
 */

namespace Drupal\striper\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\striper\StriperStripeAPI;

class StriperSubscriptionSync extends ControllerBase {

    private $stripe;
    private $config;

    public function __construct() {
        $this->config = \Drupal::config('striper.config');
        $this->stripe = new StriperStripeAPI();
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     */
    public function sync() {
        $subscribers = \Stripe\Subscription::all();

        $updated = 0;
        $inserted = 0;
        foreach($subscribers['data'] as $subscriber) {
            $customer_plan = \Drupal::database()->query("SELECT * FROM {striper_subscriptions} WHERE stripe_cid = :scid",
                                               ['scid' => $subscriber['customer']])->fetchObject();
            // we don't have a user in drupal
            if(empty($customer_plan)) {
                /**
                 * If we haven't seen this user before, we need to:
                 * 1) Build the record for striper_subscription
                 * 2) be happy
                 */
                $stripe_customer = \Stripe\Customer::retrieve($subscriber->customer);

                $drupal_user = \Drupal::database()->query("SELECT mail, uid FROM {users_field_data} WHERE mail = :mail",
                                                   [':mail' => $stripe_customer->email])->fetchObject();

                $fields = array('uid' => $drupal_user->uid,
                    'plan' => $subscriber->plan->id,
                    'stripe_cid' => $subscriber->customer,
                    'status' => $subscriber->status,
                    'plan_end' => $subscriber->current_period_end
                );

                \Drupal::database()->insert('striper_subscriptions')->fields($fields)->execute();
                $inserted++;
            } else {
                /**
                 * if we do we need to:
                 * get the user from drupal
                 * update the record in striper_subscriptions
                 * be done...
                 */

                if($customer_plan->plan_end !== $subscriber->current_period_end ||
                    $customer_plan->status !== $subscriber->status) {
                    \Drupal::database()->update('striper_subscriptions')
                        ->fields(array('status' => $subscriber->status, 'plan_end' => $subscriber->current_period_end))
                        ->condition('uid', $customer_plan->uid)
                        ->execute();
                    $updated++;
                }
            }
        }
        drupal_set_message("Updated: {$updated} records", 'status');
        drupal_set_message("Inserted: {$inserted} records", 'status');

        return $this->redirect('striper.config.subscriptions');
        //return array();
    }

}