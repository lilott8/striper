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

        $role = \Drupal\user\Entity\Role::load("stripe_subscriber");

        foreach($subscribers['data'] as $subscriber) {
            $customer_plan = \Drupal::database()->query("SELECT * FROM {striper_subscriptions} WHERE stripe_cid = :scid",
                                               ['scid' => $subscriber['customer']])->fetchObject();
            // we don't have a user in stripe
            if(empty($customer_plan)) {
                /**
                 * If we haven't seen this user before, we need to:
                 * 1) Build the record for striper_subscription
                 * 2) be happy
                 */
                $stripe_customer = \Stripe\Customer::retrieve($subscriber->customer);

                $drupal_user = user_load_by_mail($stripe_customer->email);
                if(!$drupal_user) {
                    \Drupal::logger('striper')->error($this->t("User: %mail doesn't exist",
                                                               array('%email' => $stripe_customer->email)));
                    continue;
                }

                $fields = array('uid' => $drupal_user->id(),
                    'plan' => $subscriber->plan->id,
                    'stripe_cid' => $subscriber->customer,
                    'status' => $subscriber->status,
                    'plan_end' => $subscriber->current_period_end
                );

                \Drupal::database()->insert('striper_subscriptions')->fields($fields)->execute();
                if(!$drupal_user->hasRole($role->id())) {
                    $drupal_user->addRole($role->id());
                    $drupal_user->save();
                }

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

                    // todo: figure out how to get the role...
                    $role = \Drupal\user\Entity\Role::load('stripe_subscriber');
                    \Drupal::logger('striper')->notice(print_r($role, 1));
                    $user = \Drupal\user\Entity\User::load($customer_plan->uid);
                    if(!is_null($user)) {
                        $user->addRole($role);
                        $user->save();
                        $updated++;
                    }
                }
            }
        }
        drupal_set_message("Updated: {$updated} records", 'status');
        drupal_set_message("Inserted: {$inserted} records", 'status');

        return $this->redirect('striper.config.subscriptions');
        //return array();
    }

}