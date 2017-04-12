<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/12/17
 * Time: 10:02 PM
 */

namespace Drupal\striper\Controller\Config;


use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\striper\StriperStripeAPI;
use Drupal\user\Entity\Role;
use Stripe\Subscription;
use Stripe\Customer;
use Drupal\user\Entity\User;

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
    $subscribers = Subscription::all();

    $updated = 0;
    $inserted = 0;

    $role = Role::load("stripe_subscriber");

    foreach ($subscribers['data'] as $subscriber) {
      $customerPlan = \Drupal::database()->query("SELECT * FROM {striper_subscriptions} WHERE stripe_cid = :scid",
                                                  ['scid' => $subscriber['customer']])->fetchObject();
      // We don't have a user in stripe.
      if (empty($customerPlan)) {
        /*
         * If we haven't seen this user before, we need to:
         * 1) Build the record for striper_subscription
         * 2) be happy
         */
        $stripeCustomer = Customer::retrieve($subscriber->customer);

        $drupalUser = user_load_by_mail($stripeCustomer->email);
        if (!$drupalUser) {
          \Drupal::logger('striper')->error($this->t("User: %mail doesn't exist",
                                                     array('%email' => $stripeCustomer->email)));
          continue;
        }

        $fields = array(
          'uid' => $drupalUser->id(),
          'plan' => $subscriber->plan->id,
          'stripe_cid' => $subscriber->customer,
          'status' => $subscriber->status,
          'plan_end' => $subscriber->current_period_end,
        );

        \Drupal::database()->insert('striper_subscriptions')->fields($fields)->execute();
        if (!$drupalUser->hasRole($role->id())) {
          $drupalUser->addRole($role->id());
          $drupalUser->save();
        }

        $inserted++;
      }
      else {
        /*
         * if we do we need to:
         * get the user from drupal
         * update the record in striper_subscriptions
         * be done...
         */
        if ($customerPlan->plan_end !== $subscriber->current_period_end ||
            $customerPlan->status !== $subscriber->status) {
          \Drupal::database()->update('striper_subscriptions')
            ->fields(array('status' => $subscriber->status, 'plan_end' => $subscriber->current_period_end))
            ->condition('uid', $customerPlan->uid)
            ->execute();

          // todo: figure out how to get the role...
          $role = Role::load('stripe_subscriber');
          \Drupal::logger('striper')->notice(print_r($role, 1));
          $user = User::load($customerPlan->uid);
          if (!is_null($user)) {
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
  }

}