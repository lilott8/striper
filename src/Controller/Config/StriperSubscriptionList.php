<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/11/17
 * Time: 10:01 PM
 */

namespace Drupal\striper\Controller\Config;

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
        $subscriptions = \Drupal::database()->query("SELECT u.uid, u.mail, s.stripe_cid, s.status, " .
                                                    "s.plan, u.name, s.plan_end " .
                                                    "FROM {striper_subscriptions} s JOIN {users_field_data} u " .
                                                    "ON s.uid = u.uid")->fetchAll();

        $headers['stripe_id'] = $this->t('Stripe ID');
        $headers['stripe_plan'] = $this->t('Plan');
        $headers['customer'] = $this->t('Ends');
        $headers['user'] = $this->t('User');
        $headers['status'] = $this->t('Status');

        $rows = array();
        foreach($subscriptions as $subscription) {
            array_push($rows, array($subscription->stripe_cid,
                $subscription->plan,
                \Drupal::service('date.formatter')->format($subscription->plan_end),
                $subscription->name,
                $subscription->status));
        }

        return array(
            '#type' => 'table',
            '#header' => $headers,
            '#rows' => $rows,
        );
    }

}