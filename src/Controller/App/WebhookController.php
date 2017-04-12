<?php

namespace Drupal\striper\Controller\App;

use Drupal\Core\Config\Config;
use Drupal\Core\Controller\ControllerBase;
use Drupal\striper\Entity\StriperPlanEntity;
use Drupal\striper\Form\StriperPlanFormBase;
use Drupal\striper\StriperStripeAPI;
use Stripe\Customer;
use Stripe\Event;
use Stripe\Stripe;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Class WebhookController
 *
 * @package Drupal\striper\Controller\App
 */
class WebhookController extends ControllerBase {
  private $stripe;
  private $db;
  private $readConfig;
  private $role;

  /**
   * WebhookController constructor.
   */
  public function __construct() {
    $this->stripe = new StriperStripeAPI();
    $this->db = \Drupal::database();
    $this->readConfig = parent::config('striper');
    $this->role = Role::load('stripe_subscriber');
  }

  /**
   * This handles all relevant webhooks needed.
   * @param Symfony\Component\HttpFoundation\Request $request
   *   Request of the page.
   *
   * @return array
   *   page rendered array
   */
  public function handler(Request $request) {
    $contents = $request->getContent();

    if (!$contents) {
      \Drupal::logger('striper')->warning('No content in request from Stripe.');
      return Response::HTTP_BAD_REQUEST;
    }

    try {
      $event = \GuzzleHttp\json_decode($contents);
      $type = $event->type;
      $function = str_replace('.', '', $type);
      if (method_exists($this, $function)) {
        $this->$function($event);
      }
      else {
        \Drupal::logger('striper')->alert('Method %method doesn\'t exist', array('%method' => $function));
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('striper')->warning($e->getMessage());
    }
    return array();
  }

  /**
   * @param $event json object
   *
   *
   */
  private function chargefailed($event) {

  }

  /**
   * @param $event json object
   *
   *
   */
  private function chargepending($event) {

  }

  /**
   * @param $event json object
   *
   *
   */
  private function chargerefunded($event) {

  }

  /**
   * @param $event json object
   *
   *
   */
  private function chargeupdated($event) {

  }

  /**
   * @param $event json object
   *
   *
   */
  private function chargesucceeded($event) {

  }

  /**
   * @param $event json object
   *
   *
   */
  private function customercreated($event) {
    \Drupal::logger('striper')->error(t('We have to do something on customer creation'));
  }

  /**
   * @param $event json object
   *
   *
   */
  private function customerdeleted($event) {
    $striperUserId = $event->data->object->customer;
    // Check the local db first -- it's less expensive.
    $stripeUser = $this->db->query('SELECT * FROM {striper_subscriptions} s WHERE s.stripe_cid = %id',
                                   array('%id' => $striperUserId))->fetchObject();

    if (is_null($stripeUser)) {
      $email = Customer::retrieve($striperUserId)->email;
      \Drupal::logger('striper')->error(t('No drupal user exists for %email.',
                                          array('%email' => $email)));
    }
    else {
      $user = User::load($stripeUser->uid);
      $user->removeRole($this->role->id());
      $user->save();
    }
  }

  /**
   * @param $event json object
   *
   *
   */
  private function customerupdated($event, $stripe_user = NULL) {
    // don't do anything right now...
  }

  /**
   * @param $event json object
   *
   * This retrieves the user by email and
   * inserts a customer record in stripers db
   */
  private function customersubscriptioncreated($event) {
    $stripeUser = $event->data->object->customer;
    // Check the local db first -- it's less expensive.
    $striper = $this->db->query('SELECT * FROM {striper_subscriptions} s WHERE s.stripe_cid = :id',
                                array(':id' => $stripeUser))->fetchObject();
    // If we don't have a record, we need to create one.
    if (!is_null($striper) || !empty($striper)) {
      // Handle the case for us having the user saved to the database.
      $stripeUser = Customer::retrieve($stripeUser);

      // Load a mutable entity of the user.
      $user = User::load($this->db->query('SELECT u.uid FROM {users_field_data} u WHERE u.mail = :email',
                                          array(':email' => $stripeUser->email))->fetchObject()->uid);

      $fields = array(
        'uid' => $user->id(),
        'plan' => $event->data->object->plan->id,
        'stripe_cid' => $stripeUser->id,
        'stripe_sid' => $event->data->object->id,
        'status' => SUBSCRIPTION_STATES['active'],
        'plan_end' => $event->data->object->current_period_end,
      );
      $this->db->insert('striper_subscriptions')->fields($fields)->execute();

      // Add the role to the user.
      $user->addRole($this->role->id());
      $user->save();
    }

    return;
  }

  /**
   * @param $event json object
   *
   * This retrieves the user record by customer id from
   * the striper tables, then invalidates the user's account
   * could also send them an email, if we want in the future
   */
  private function customersubscriptiondeleted($event) {
    $striperUserId = $event->data->object->customer;
    // Check the local db first -- it's less expensive.
    $stripeUser = $this->db->query('SELECT * FROM {striper_subscriptions} s WHERE s.stripe_cid = :id',
                                    array(':id' => $striperUserId))->fetchObject();

    if (empty($stripeUser) || is_null($stripeUser)) {
      \Drupal::logger('striper')->warning(t('Subscription Delete: Drupal has no user by %cid',
                                            array('%cid' => $striperUserId)));
      return;
    }

    $user = User::load($stripeUser->uid);

    $fields = array(
        'plan' => 'none',
        'plan_end' => -1,
        'status' => SUBSCRIPTION_STATES['inactive'],
    );
    $this->db->update('striper_subscriptions')->fields($fields)->condition('uid', $user->id())->execute();

    $user->removeRole($this->role->id());
    $user->save();
  }

  /**
   * @param $event json object
   *
   * placeholder for right now
   */
  private function customersubscriptiontrial_will_end($event) {

  }

  /**
   * @param $event json object
   * @param $striper_user striper_subscription record
   *
   * This retrieves the user record by customer id from
   * the striper tables, then updates the end time of the
   * users account, or cancels their account if it's expired/cancelled
   */
  private function customersubscriptionupdated($event, $stripeUser = NULL) {
    $stripeUser = $this->db->query("SElECT * FROM {striper_subscriptions} s WHERE stripe_cid = :id",
                                   array(':id' => $event->data->object->customer));

    $user = user_load_by_mail($event->data->object->metadata->email);

    if (is_null($stripeUser) || empty($stripeUser)) {
      $this->customersubscriptioncreated($event);
    }
    else {
      $fields = array(
          'plan_end' => $event->data->object->current_period_end,
          'plan' => $event->data->object->plan->id,
          'status' => SUBSCRIPTION_STATES['active'],
      );

      $this->db->update('striper_subscriptions')->fields($fields)->condition('uid', $user->id())->execute();

      $role = Role::load('stripe_subscriber');
      if (!$user->hasRole($role->id())) {
        $user->addRole($role->id());
        $user->save();
      }
    }
    return;
  }

  /**
   * @param $event json object
   *
   * creates a plan in the configs to be exposed to
   * the user for subscription, if we have the record
   * already, we update it
   */
  private function plancreated($event) {
    $plan = \Drupal::config("striper.striper_plan.{$event->data->object->id}");
    if (!is_null($plan)) {
      $this->planupdated($event);
    }
    else {
      $values = array(
          'id' => $event->data->object->id,
          'plan_active' => TRUE,
          'plan_default' => FALSE,
          'plan_source' => StriperPlanFormBase::SOURCE['stripe'],
          'plan_name' => $event->data->object->name,
          'plan_price' => $event->data->object->amount,
          'plan_frequency' => "{$event->data->object->interval_count}-{$event->data->object->interval}",
      );
      if (!empty($event->data->object->metadata->description) && !is_null($event->data->object->metadata->description)) {
        $values['plan_description'] = $event->data->object->metadata->description;
      }
      $plan = StriperPlanEntity::create($values);
      $plan->save();
    }
  }

  /**
   * @param $event json object
   *
   * updates an existing plan.  If we don't have one,
   * we create it.
   */
  private function planupdated($event) {
    $plan = \Drupal::configFactory()->getEditable("striper.striper_plan.{$event->data->object->id}");
    if (!is_null($plan)) {
      $values = array(
          'id' => $event->data->object->id,
          'plan_active' => TRUE,
          'plan_default' => $plan->get('plan_default'),
          'plan_source' => $plan->get('plan_source'),
          'plan_name' => $event->data->object->name,
          'plan_price' => $event->data->object->amount,
          'plan_frequency' => "{$event->data->object->interval_count}-{$event->data->object->interval}",
      );
      if (!empty($event->data->object->metadata->description) && !is_null($event->data->object->metadata->description)) {
        $values['plan_description'] = $event->data->object->metadata->description;
      }
      $plan->setData($values);
      $plan->save();
      drupal_set_message($this->t('Plan %name has been updated.', array('%name' => $plan->get('plan_name'))));
      \Drupal::logger('striper')->notice('Plan %name has been updated.', ['%name' => $plan->get('plan_name')]);
    } else {
      $this->plancreated($event);
    }
  }

  /**
   * @param $event json object
   *
   * deletes a config from drupal
   */
  private function plandeleted($event) {
    $plan = \Drupal::configFactory()->getEditable("striper.striper_plan.{$event->data->object->id}");
    if (!is_null($plan)) {
      $plan->set('plan_active', FALSE);
      $plan->save();
      //$plan->delete();
      drupal_set_message($this->t('Plan %name has been deleted.', array('%name' => $plan->get('plan_name'))));
      \Drupal::logger('striper')->notice('Plan %name has been deleted.', ['%name' => $plan->get('plan_name')]);
    }

  }
}