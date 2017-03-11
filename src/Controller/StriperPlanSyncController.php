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
        $this->config = \Drupal::config('striper.config');
        if($this->config->get('use_key') == 'live') {
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

        $syncedPlans = 0;
        foreach($plans['data'] as $plan) {
            \Drupal::logger('stripe_sync')->notice($plan['id']);
            if(is_null(\Drupal::entityTypeManager()->getStorage('striper_plan')->load($plan['id']))) {
                $entity = \Drupal::entityTypeManager()->getStorage('striper_plan')->create(
                    array(
                        'id' => str_replace('-', '_', preg_replace('@[^a-z0-9-]+@', '_', strtolower($plan['id']))),
                        'machine_name' => str_replace('-', '_', preg_replace('@[^a-z0-9-]+@', '_', strtolower($plan['id']))),
                        'plan_name' => $plan['name'],
                        'plan_price' => $plan['price']/100,
                        'plan_frequency' => $this->buildFrequency($plan),
                        'plan_active' => TRUE,
                        'plan_source' => 'stripe',
                        'plan_stripe_id' => $plan['id'],
                    )
                );
                $result = $entity->save();
                if($result == SAVED_NEW || $result == SAVED_UPDATED) {
                    $syncedPlans++;
                }
            }
        }

        drupal_set_message($this->t("Imported/Updated %records from Stripe", array('%records' => $syncedPlans)));
        return $this->redirect('entity.striper_plan.list');
    }

    private function buildFrequency($plan) {
        return "{$plan['interval_count']}-{$plan['interval']}";
    }
}