<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/12/17
 * Time: 10:02 PM
 */

namespace Drupal\striper\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\striper\StriperStripeAPI;

class StriperSubscriptionSync extends ControllerBase {

    private $stripe;
    private $config;

    public function __construct() {
        $this->config = \Drupal::config('striper.config');
        $this->stripe = new StriperStripeAPI();
    }

    public function sync() {
        $subscribers = \Stripe\Subscription::all();

        $updated = 0;
        $inserted = 0;
        foreach($subscribers['data'] as $subscriber) {
            $user = \Drupal::database()->query("SELECT uid FROM {striper_subscriptions} WHERE stripe_cid = :scid",
                                               ['scid' => $subscriber['customer']])->fetchField();
            if(empty($user)) {
                $stripe_customer = \Stripe\Customer::retrieve($subscriber['customer']);
                \Drupal::logger('striper')->notice($stripe_customer);
            }
            \Drupal::logger('striper')->notice($subscriber['customer'] . "\t User: " . $user);
            // if $subscriber['customer'] then update db

            // else add it
        }


        return array(
            '#type' => 'markup',
            '#markup' => $this->t('Hello, World!'),
        );
    }

}