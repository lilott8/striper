<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/12/17
 * Time: 10:05 PM
 */

namespace Drupal\striper;


class StriperStripeAPI {

    public $secretKey;
    public $publicKey;
    private $config;

    public function __construct() {
        $this->config = \Drupal::config('striper.config');
        if($this->config->get('use_key') == 'live') {
            $this->secretKey = Form\StriperConfigKeyForm::getKey(Form\StriperConfigKeyForm::STRIPE_LIVE_SK);
            $this->publicKey = Form\StriperConfigKeyForm::getKey(Form\StriperConfigKeyForm::STRIPE_LIVE_PK);
        } else {
            $this->secretKey = Form\StriperConfigKeyForm::getKey(Form\StriperConfigKeyForm::STRIPE_TEST_SK);
            $this->publicKey = Form\StriperConfigKeyForm::getKey(Form\StriperConfigKeyForm::STRIPE_TEST_PK);
        }

        \Stripe\Stripe::setApiKey($this->secretKey);
    }
}