<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/9/17
 * Time: 1:28 PM
 */

namespace Drupal\striper;


use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class StriperAccessController extends EntityAccessControlHandler {
    public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
        // admin always has access to all the things
        if((int)$account->id() === 1) {
            return AccessResult::allowed();
        }

        if(($operation == 'update' || $operation == 'delete') && $account->hasPermission('administer striper')) {
            return AccessResult::allowed();
        }

        if($operation == 'view' && $account->hasPermission('view stripe plans')) {
            return AccessResult::allowed();
        }

        return AccessResult::forbidden();
    }
}