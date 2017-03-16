<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/15/17
 * Time: 4:07 PM
 */

namespace Drupal\striper\Controller\App;


use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\striper;

class StriperPlansPage extends ControllerBase {

    public function viewPlans() {
        if($this->currentUser()->isAnonymous()) {
            return Url::fromRoute('user.page');
        }

        $plans = array();

        foreach(\Drupal::entityTypeManager()->getStorage('striper_plan')->loadMultiple() as $plan) {
            if(($plan->plan_source == 'stripe' || $plan->plan_source == 'user') && $plan->plan_active) {
                $cost = striper\StriperStripeAPI::formatCost($plan->plan_cost, TRUE);
                $plans[$plan->id()] = $this->t("%name -- %cost",
                                               array('%name' => $plan->plan_name,
                                                   '%cost' => $this->getFrequency($cost, $plan->plan_frequency)));
            }
        }

        return array(
            '#theme' => 'item_list',
            '#items' => $plans,
            '#title' => 'hi',
            '#additional' => array(
                'libraries' => array('striper/striper.stripejs')
            )
        );
    }

    private function getTrendyHtmlPlans() {
        return "here";
    }

    private function getFrequency($cost, $freq) {
        $split = explode('-', $freq);
        return "{$cost} every {$split[0]} {$split[1]}(s)";
    }

}