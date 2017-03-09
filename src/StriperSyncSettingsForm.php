<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/8/17
 * Time: 8:48 PM
 */

namespace Drupal\striper;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class StriperSyncSettingsForm extends ConfigFormBase {

    public function __construct(ConfigFactoryInterface $config_factory) {

        parent::__construct($config_factory);
        $this->config = \Drupal::config('striper.config');

        if($this->config->get('use_key') == 'live') {
            $message = 'You are using your live key.';
            $key = StriperKeySettingsForm::getKey(StriperKeySettingsForm::STRIPE_LIVE_SK);
        } else {
            $message = '';
            $key = StriperKeySettingsForm::getKey(StriperKeySettingsForm::STRIPE_TEST_SK);
        }

        if(!empty($message)) {
            drupal_set_message($message, 'warning');
        }

        \Stripe\Stripe::setApiKey($key);

    }

    protected function getEditableConfigNames() {
        // TODO: Implement getEditableConfigNames() method.
        return ['striper.settings.sync'];
    }

    public function getFormId() {
        // TODO: Implement getFormId() method.
        return 'striper_admin_settings';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {

        $plans = \Stripe\Plan::all();

        $table = array();

        foreach($plans['data'] as $plan) {
            var_dump($plan);
        }

        return parent::buildForm($form, $form_state); // TODO: Change the autogenerated stub
    }
}