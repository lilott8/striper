<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/11/17
 * Time: 10:01 PM
 */

namespace Drupal\striper\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\striper\StriperStripeAPI;
use \Symfony\Component\HttpFoundation\Response;
use Drupal\striper\Form;

/**
 * Class StriperSubscriptionList
 *
 * @package Drupal\striper\Controller
 */

class StriperSubscriptionList extends ControllerBase {

    private $stripe;
    private $config;

    public function __construct() {
        $this->config = \Drupal::config('striper.config');
        $this->stripe = new StriperStripeAPI();
    }

    public function listUsers() {

        $subscribers = \Stripe\Subscription::all();

        $headers['stripe_id'] = $this->t('Stripe ID');
        $headers['stripe_plan'] = $this->t('Plan');
        $headers['customer'] = $this->t('Customer');
        $headers['status'] = $this->t('Status');

        $rows = array();
        foreach($subscribers['data'] as $subscription) {

        }

        return array(
            '#type' => 'table',
            '#header' => $headers,
            '#rows' => $rows,
        );
    }

}