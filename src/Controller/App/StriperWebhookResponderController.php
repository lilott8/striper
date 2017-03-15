<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/7/17
 * Time: 8:12 PM
 */

namespace Drupal\striper\Controller\App;

use Drupal\Core\Controller\ControllerBase;

class StriperWebhookResponderController extends ControllerBase {

    public function subscriptions() {
        return array(
            '#type' => 'markup',
            '#markup' => $this->t('Hello, World!'),
        );
    }
}