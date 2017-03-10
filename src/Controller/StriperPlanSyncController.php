<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/10/17
 * Time: 1:13 PM
 */

namespace Drupal\striper\Controller;

use Drupal\striper;
use Drupal\Core\Controller\ControllerBase;

class StriperPlanSyncController extends ControllerBase {

    private $secretKey;
    private $publicKey;
    private $config;

    function __construct() {
        $config = \Drupal::config('striper.config');
        if($config->get('use_key') == 'live') {
            $this->secretKey = striper\StriperConfigKeyForm::getKey(striper\StriperConfigKeyForm::STRIPE_LIVE_SK);
            $this->publicKey = striper\StriperConfigKeyForm::getKey(striper\StriperConfigKeyForm::STRIPE_LIVE_PK);
        } else {
            $this->secretKey = striper\StriperConfigKeyForm::getKey(striper\StriperConfigKeyForm::STRIPE_TEST_SK);
            $this->publicKey = striper\StriperConfigKeyForm::getKey(striper\StriperConfigKeyForm::STRIPE_TEST_PK);
        }

        \Stripe\Stripe::setApiKey($this->secretKey);
    }

    public function sync() {
        $plans = \Stripe\Plan::all();
        \Drupal::logger('stripe_sync')->notice($plans);

        foreach($plans['data'] as $plan) {
            \Drupal::logger('stripe_sync')->notice($plan['id']);
            /*\Drupal::entityTypeManager()->getStorage('striper_plan')->create(
                array(
                    'machine_name' => preg_replace('@[^a-z0-9-]+@','-', strtolower($plan['id'])),
                    'plan_name' => $plan['name'],
                    'plan_price' => $plan[''],
                    'plan_frequency' => $this->buildFrequency($plan),
                    'plan_active' => TRUE,
                    'plan_source' => 'stripe',
                    'plan_stripe_id' => $plan['id'],
                    )
                /**
                 *         $row['plan_name'] = $entity->plan_name;
                $row['machine_name'] = $entity->id();
                $row['price'] = $entity->plan_price;
                $row['frequency'] = $entity->plan_frequency;
                $row['active_plan'] = $entity->plan_active ? "enabled" : "disabled";
                $row['plan_source'] = $entity->plan_source;
                 /
            );*/
        }

        drupal_set_message("Sync was successful!");
        return $this->redirect('entity.striper_plan.list');
    }

    private function buildFrequency($plan) {
        
    }
}