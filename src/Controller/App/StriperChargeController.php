<?php

namespace Drupal\striper\Controller\App;

use Drupal\striper;
use Stripe\Charge;
use Drupal\striper\StriperStripeAPI;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\user\Entity\User;
use Drupal\user\Entity\Role;

/**
 * Manages the charging of a customer.
 *
 * @package Drupal\striper\Controller\App
 */
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
    $this->striperApi = new StriperStripeAPI();
    $this->db = \Drupal::database();
  }

  /**
   *
   * @throws Exception
   *  sdfaf
   *
   * @return string
   *   Return Hello string.
   */

  /**
   * Charge the user
   * 1) See if the user has a subscription
   *  2) if we don't have them in stripe, add them
   *  3) If they have a subscription and not the same, update
   *  4) if it is the same and still subscribed, do nothing.
   *
   * @param \Symfony\Component\HttpFoundation|Request $request
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   * @throws \Exception
   */
  public function charge(Request $request) {
    // Load the user.
    $user = \Drupal::currentUser()->getAccount();
    // Get the striper config from the entityconfig store.
    $config = \Drupal::configFactory()->get('striper.striper_plan.' . $request->get('plan_name'));
    // Load the price from the config that was purchased.
    // $amount = $config->get('plan_price');
    // Token from stripe.
    $token = $request->get('stripeToken');

    if (!$token || (is_null($config) || empty($config))) {
      throw new \Exception("Required data is missing!");
    }
    $message = t("You've successfully subscribed to plan %plan",
                 array('%array' => $config->get('plan_name')));
    $status = 'status';

    // Load the striper object from the database.
    $striperUser = $this->db->query("SELECT * FROM {striper_subscriptions} s WHERE s.uid = :id",
                                     array(':id' => $user->id()))->fetchObject();

    try {
      // If we don't have a user, create them.
      if (is_null($striperUser) || empty($striperUser)) {
        // Otherwise we must create the user in Stripe.
        $customer = Customer::create(
            array(
              'email' => $user->getEmail(),
              'source' => $token,
              'metadata' => array(
                'plan' => $request->get('id'),
                'email' => $user->getEmail(),
                'uid' => $user->id(),
              ),
            )
        );
        $customerId = $customer->id;
        $fields = array(
          'uid' => $user->id(),
          'stripe_cid' => $customerId,
        );
        // Save the customer id in Drupal's database.
        $this->db->insert('striper_subscriptions')->fields($fields)->execute();
      }
      else {
        // Otherwise, grab the customer id from the db.
        $customerId = $striperUser->stripe_cid;
      }

      $subscription = NULL;
      // We don't have a user thus, we don't have a subscription.
      if (is_null($striperUser) || empty($striperUser)) {
        // Create the subscription from the user.
        $subscription = Subscription::create(
            array(
              "customer" => $customerId,
              "plan" => $config->get('id'),
              "trial_end" => "now",
              'metadata' => array(
                'plan' => $config->get('id'),
                'email' => $user->getEmail(),
                'uid' => $user->id(),
              ),
            )
        );
      }
      else {
        // Change the plan if expired or the plan has changed.
        $subscription = Subscription::retrieve($striperUser->stripe_sid);
        $subscription->plan = $config->get('id');
        $subscription->save();
      }

      // We have a problem with the subscription.
      if (is_null($subscription) || empty($subscription)) {
        $status = 'error';
        $message = t('There was an error processing your payment.');
        \Drupal::logger('striper')->warning(striper\StriperDebug::vd($subscription));
      }
      else {
        // We want to updated the subscription record within striper.
        $fields = array(
          'plan' => $config->get('id'),
          'status' => SUBSCRIPTION_STATES['active'],
          'plan_end' => $subscription->current_period_end,
          'stripe_sid' => $subscription->id,
        );
        $this->db->update('striper_subscriptions')->fields($fields)
          ->where('uid = :uid', array(':uid' => $user->id()))->execute();

        \Drupal::logger('striper')->info(t("Successfully processed Stripe charge. This should have triggered a Stripe webhook"));

        // Add the role to the user.
        $user = User::load($this->currentUser()->id());
        $user->addRole(Role::load('stripe_subscriber')->id());
        $user->save();

        // In addition to the webhook,
        // we fire a traditional Drupal hook to
        // permit other modules to respond to this event instantaneously.
        $this->moduleHandler()->invokeAll('stripe_checkout_charge_succeeded', [
          $subscription,
          $config,
          $user,
        ]);
      }
      drupal_set_message($message, $status);
      // return $this->redirect('striper.app.user.subscriptions');
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