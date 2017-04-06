<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/7/17
 * Time: 8:12 PM
 */

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

class WebhookController extends ControllerBase {

    private $stripe;
    private $db;
    private $readConfig;

    public function __construct() {
        $this->stripe = new StriperStripeAPI();
        $this->db = \Drupal::database();
        $this->readConfig = parent::config('striper');
    }

    /**
     * @param Request $request
     *
     * This will do a few things:
     * validate user subscriptions on auto pay,
     * remove users who have cancelled,
     * email users on failed subscriptions
     *
     * @return int
     */
    public function handler(Request $request) {
        \Drupal::logger('striper')->alert("sanity check %num", array('%num'=> rand(0,100)));
        $contents = $request->getContent();

        //\Drupal::logger('striper')->debug($contents);

        if(!$contents) {
            \Drupal::logger('striper')->warning('No content in request from Stripe.');
            return Response::HTTP_BAD_REQUEST;
        }

        try {
            $event = \GuzzleHttp\json_decode($contents);
            $type = $event->type;
            $function = str_replace('.','', $type);
            \Drupal::logger('striper')->debug($function);
            if(method_exists($this, $function)) {
                $this->$function($event);
            } else {
                \Drupal::logger('striper')->alert('Method %method doesn\'t exist', array('%method'=>$function));
            }
        } catch (\Exception $e) {
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
        
    }

    /**
     * @param $event json object
     *
     *
     */
    private function customerdeleted($event) {
        $striper_user_id = $event->data->object->customer;
        // check the local db first -- it's less expensive
        $stripe_user = $this->db->query('SELECT * FROM {striper_subscriptions} s WHERE s.stripe_cid = %id',
                                        array('%id' => $striper_user_id))->fetchObject();

        if(is_null($stripe_user)) {
            $email = \Stripe\Customer::retrieve($striper_user_id)->email;
            \Drupal::logger('striper')->error(t('No drupal user exists for %email.',
                                                array('%email' => $email)));
        } else {
            $user = \Drupal\user\Entity\User::load($stripe_user->uid);

            $user->removeRole(\Drupal\user\Entity\Role::load('stripe_subscriber')->id());
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
        $striper_user_id = $event->data->object->customer;
        // check the local db first -- it's less expensive
        $stripe_user = $this->db->query('SELECT * FROM {striper_subscriptions} s WHERE s.stripe_cid = :id',
                                 array(':id' => $striper_user_id))->fetchObject();

        // if we have a user, then we check ending dates
        // if the ending dates are the same, we return,
        // if not, we can call customer updated...
        if(!is_null($stripe_user)) {
            if($stripe_user->plan_end == $event->data->object->current_period_end) {
                return;
            } else {
               $this->customersubscriptionupdated($event, $stripe_user);
            }
        } else {
            $stripe_user = \Stripe\Customer::retrieve($striper_user_id);
        }

        $user = $this->db->query('SELECT u.uid, u.uid FROM {users_field_data} u WHERE u.mail = :email',
                                 array(':email' => $stripe_user->email))->fetchObject();

        $user = \Drupal\user\Entity\User::load($user->uid);

        if(is_null($user)) {
            \Drupal::logger('striper')->warning(t('Could not find user with email: %e',
                                                  array('%e' => $stripe_user->email)));
            return;
        }

        $fields = array('uid' => $user->uid,
            'plan' => $event->data->object->plan->id,
            'stripe_cid' => $stripe_user->id,
            'status' => $event->data->object->status,
            'plan_end' => $event->data->object->current_period_end
        );

        \Drupal::database()->insert('striper_subscriptions')->fields($fields)->execute();

        $user->addRole(\Drupal\user\Entity\Role::load('stripe_subscriber')->id());
        $user->save();
    }

    /**
     * @param $event json object
     *
     * This retrieves the user record by customer id from
     * the striper tables, then invalidates the user's account
     * could also send them an email, if we want in the future
     */
    private function customersubscriptiondeleted($event) {
        $striper_user_id = $event->data->object->customer;
            // check the local db first -- it's less expensive
        $stripe_user = $this->db->query('SELECT * FROM {striper_subscriptions} s WHERE s.stripe_cid = :id',
                                        array(':id' => $striper_user_id))->fetchObject();

        if(empty($stripe_user) || is_null($stripe_user)) {
            \Drupal::logger('striper')->warning(t('Subscription Delete: Drupal has no user by %cid',
                                                  array('%cid' => $striper_user_id)));
            return;
        }

        $user = \Drupal\user\Entity\User::load($stripe_user->uid);
        $role = \Drupal\user\Entity\Role::load('stripe_subscriber');


        $fields = array(
            'status' => 'inactive',
            'plan' => 'none',
            'plan_end' => -1,
        );
        $this->db->update('striper_subscriptions')->fields($fields)->execute();

        $user->removeRole($role->id());
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
    private function customersubscriptionupdated($event, $stripe_user = NULL) {
        $striper_user_id = $event->data->object->customer;
        if(is_null($stripe_user)) {
            // check the local db first -- it's less expensive
            $stripe_user = $this->db->query('SELECT * FROM {striper_subscriptions} s WHERE s.stripe_cid = :id',
                                            array(':id' => $striper_user_id))->fetchObject();
        }

        if(empty($stripe_user) || is_null($stripe_user)) {
            \Drupal::logger('striper')->warning(t('Subscription Create: Drupal has no user by %cid',
                                                  array('%cid' => $striper_user_id)));
            return;
        }

        $user = \Drupal\user\Entity\User::load($stripe_user->uid);
        $role = \Drupal\user\Entity\Role::load('stripe_subscriber');

        if($event->data->object->current_period_end <= \Drupal::service('date.formatter')->format(time())) {
            $user->removeRole($role->id());
            $user->save();
        } else {
            if(!$user->hasRole($role->id())) {
                $user->addRole($role->id());
                $user->save();
            }
        }
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
        if(!is_null($plan)) {
            $this->planupdated($event);
        } else {
            $values = array(
                'id' => $event->data->object->id,
                'plan_active' => TRUE,
                'plan_default' => FALSE,
                'plan_source' => StriperPlanFormBase::SOURCE['stripe'],
                'plan_name' => $event->data->object->name,
                'plan_price' => $event->data->object->amount,
                'plan_frequency' => "{$event->data->object->interval_count}-{$event->data->object->interval}",
            );
            if(!empty($event->data->object->metadata->description) && !is_null($event->data->object->metadata->description)) {
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
        if(!is_null($plan)) {
            $values = array(
                'id' => $event->data->object->id,
                'plan_active' => TRUE,
                'plan_default' => $plan->get('plan_default'),
                'plan_source' => $plan->get('plan_source'),
                'plan_name' => $event->data->object->name,
                'plan_price' => $event->data->object->amount,
                'plan_frequency' => "{$event->data->object->interval_count}-{$event->data->object->interval}",
                'plan_description' => $event->data->object->metadata->description,
            );
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
        if(!is_null($plan)) {
            $plan->delete();
            drupal_set_message($this->t('Plan %name has been deleted.', array('%name' => $plan->get('plan_name'))));
            \Drupal::logger('striper')->notice('Plan %name has been deleted.', ['%name' => $plan->get('plan_name')]);
        }

    }
}