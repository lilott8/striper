<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/10/17
 * Time: 1:13 PM
 */

namespace Drupal\striper\Controller\Config;

use Drupal\striper\Form;
use Drupal\Core\Controller\ControllerBase;
use Drupal\striper\StriperStripeAPI;

class StriperPlanSyncController extends ControllerBase {

    private $stripe;
    private $config;

    function __construct() {
        $this->config = \Drupal::config('striper.config');
        $this->stripe = new StriperStripeAPI();

        \Stripe\Stripe::setApiKey($this->stripe->secretKey);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function sync() {
        $plans = \Stripe\Plan::all();

        $syncedPlans = 0;
        foreach($plans['data'] as $plan) {
            $machine_name = str_replace('-', '_', preg_replace('@[^a-z0-9-]+@', '_', strtolower($plan['id'])));
            if (is_null(\Drupal::entityTypeManager()->getStorage('striper_plan')->load($machine_name))) {
                $entity = \Drupal::entityTypeManager()->getStorage('striper_plan')->create(
                    array(
                        'id' => $machine_name,
                        'machine_name' => $machine_name,
                        'plan_name' => $plan['name'],
                        'plan_price' => $plan['amount'],
                        'plan_frequency' => $this->buildFrequency($plan),
                        'plan_active' => TRUE,
                        'plan_source' => 'stripe',
                        'plan_stripe_id' => $plan['id'],
                    )
                );
                $result = $entity->save();

                if ($result == SAVED_NEW || $result == SAVED_UPDATED) {
                    $syncedPlans++;
                }
                $role = \Drupal\user\Entity\Role::load("stripe_subscriber");
                if(!is_null($role)) {
                    $role->grantPermission($machine_name);
                    $role->save();
                }
            } else {
                \Drupal::logger('striper')->notice($this->t("%id already exists", array('%id' => $machine_name)));
            }
        }

        if($syncedPlans > 0) {
            drupal_set_message($this->t("Imported/Updated %records from Stripe", array('%records' => $syncedPlans)));
        } else {
            drupal_set_message($this->t("No Stripe records found/altered"), 'notice');
        }
        return $this->redirect('entity.striper_plan.list');
    }

    private function buildFrequency($plan) {
        return "{$plan['interval_count']}-{$plan['interval']}";
    }
}