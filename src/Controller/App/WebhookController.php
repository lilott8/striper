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
use Stripe\Event;
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

        \Drupal::logger('striper')->debug($contents);

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

    private function chargefailed($event) {

    }

    private function chargepending($event) {

    }

    private function chargerefunded($event) {

    }

    private function chargeupdated($event) {

    }

    private function chargesucceeded($event) {

    }

    private function customercreated($event) {
        
    }

    private function customerdeleted($event) {

    }

    private function customerupdated($event) {

    }

    private function customersubscriptioncreated($event) {

    }

    private function customersubscriptiondeleted($event) {

    }

    private function customersubscriptiontrial_will_end($event) {

    }

    private function customersubscriptionupdated($event) {

    }

    private function plancreated($event) {
        $plan = \Drupal::config("striper.striper_plan.{$event->data->object->id}");
        if(!is_null($plan)) {
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
        $this->planupdated($event);
    }

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
        }
    }

    private function plandeleted($event) {
        $plan = \Drupal::configFactory()->getEditable("striper.striper_plan.{$event->data->object->id}");
        if(!is_null($plan)) {
            $plan->delete();
            drupal_set_message($this->t('Plan %name has been updated.', array('%name' => $plan->get('plan_name'))));
            \Drupal::logger('striper')->notice('Plan %name has been updated.', ['%name' => $plan->get('plan_name')]);
        }

    }
}