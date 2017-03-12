<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/11/17
 * Time: 9:04 PM
 */

namespace Drupal\striper;


class StriperPermissions {

    public function permissions() {
        $permissions = [];
        // Generate permissions for each plan that exist.
        $plans = \Drupal::entityTypeManager()->getStorage('striper_plan')->loadMultiple();
        uasort($plans, 'Drupal\Core\Config\Entity\ConfigEntityBase::sort');
        foreach ($plans as $plan) {
            if ($plan->plan_active) {
                $permissions[$plan->id] = [
                    'title' => $plan->label(),
                    'description' => t("Defines the %frequency subscription from %source",
                                       array('%frequency' => $plan->label(), '%source' => $plan->plan_source)),
                ];
            }
        }
        return $permissions;
    }

}