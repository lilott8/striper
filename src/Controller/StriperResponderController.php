<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/7/17
 * Time: 8:12 PM
 */

namespace Drupal\striper\Controller;

use Drupal\Core\Controller\ControllerBase;

class StriperResponderController extends ControllerBase {

    public static function respond() {
        \Drupal::logger('stripe_webhook')->notice('here');
    }
}