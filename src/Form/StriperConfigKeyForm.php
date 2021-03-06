<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/7/17
 * Time: 8:07 PM
 */

namespace Drupal\striper\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class StriperConfigKeyForm extends ConfigFormBase {
  const STRIPE_TEST_PK = 'stripe_test_publishable';
  const STRIPE_TEST_SK = 'stripe_test_secret';
  const STRIPE_LIVE_PK = 'stripe_live_publishable';
  const STRIPE_LIVE_SK = 'stripe_live_secret';

  /**
   * @inheritdoc
   */
  protected function getEditableConfigNames() {
    return ['striper.config.keys'];
  }

  /**
   * @inheritdoc
   */
  public function getFormId() {
    return 'striper_admin_config';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('striper.config');

    $form['striper_test_keys'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Testing Keys'),
    );

    $form['striper_test_keys']['test_secret_key'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Test secret key'),
        '#default_value' => StriperConfigKeyForm::getKey(StriperConfigKeyForm::STRIPE_TEST_SK),
        '#size' => 60,
        '#maxlength' => 128,
        '#required' => TRUE,
        '#attributes' => array('readonly' => 'readonly'),
    );

    $form['striper_test_keys']['test_publishable_key'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Test publishable key'),
        '#default_value' => StriperConfigKeyForm::getKey(StriperConfigKeyForm::STRIPE_TEST_PK),
        '#size' => 60,
        '#maxlength' => 128,
        '#required' => TRUE,
        '#attributes' => array('readonly' => 'readonly'),
    );

    $form['striper_live_keys'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Live Keys'),
    );

    $form['striper_live_keys']['live_secret_key'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Live secret key'),
        '#default_value' => StriperConfigKeyForm::getKey(StriperConfigKeyForm::STRIPE_LIVE_SK),
        '#size' => 60,
        '#maxlength' => 128,
        '#attributes' => array('readonly' => 'readonly'),
    );

    $form['striper_live_keys']['live_publishable_key'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Live publishable key'),
        '#default_value' => StriperConfigKeyForm::getKey(StriperConfigKeyForm::STRIPE_LIVE_PK),
        '#size' => 60,
        '#maxlength' => 128,
        '#attributes' => array('readonly' => 'readonly'),
    );

    $keys = array(
        1 => $this->t('Testing Keys'),
        2 => $this->t('Live Keys'),
    );

    if (!is_null($config->get('use_key'))) {
      $usage = $config->get('use_key') == 'live' ? 2 : 1;
    } else {
      $usage = 1;
    }

    $form['striper_use_key'] = array(
        '#type' => 'radios',
        '#title' => $this->t('Use which key'),
        '#default_value' => $usage,
        '#options' => $keys,
        '#required' => TRUE,
        '#descriptions' => $this->t("Select 'Live keys' for production only"),
    );

    return parent::buildForm($form, $form_state);
  }

  function validateForm(array &$form, FormStateInterface $form_state) {

    switch($form_state->getValue('striper_use_key')) {
      case 1:
        if (empty($form_state->getValue('test_secret_key'))) {
          $form_state->setErrorByName('test_secret_key');
          drupal_set_message("Please add your keys to the key module in accordance with the 'help' section", 'error');
        }
        if(empty($form_state->getValue('test_publishable_key'))) {
          $form_state->setErrorByName('test_publishable_key');
          drupal_set_message("Please add your keys to the key module in accordance with the 'help' section", 'error');
        }
        break;
      case 2:
        if (empty($form_state->getValue('live_secret_key'))) {
          $form_state->setErrorByName('live_secret_key');
          drupal_set_message("Please add your keys to the key module in accordance with the 'help' section", 'error');
        }
        if (empty($form_state->getValue('live_publishable_key'))) {
          $form_state->setErrorByName('live_publishable_key');
          drupal_set_message("Please add your keys to the key module in accordance with the 'help' section", 'error');
        }
        break;
    }

    parent::validateForm($form, $form_state);
  }

  function submitForm(array &$form, FormStateInterface $form_state) {
    switch($form_state->getValue('striper_use_key')) {
      default:
      case 1:
        $key = 'test';
        break;
      case 2:
        $key = 'live';
        break;
    }
    \Drupal::service('config.factory')->getEditable('striper.config')->set('use_key', $key)->save();
    parent::submitForm($form, $form_state); // TODO: Change the autogenerated stub
  }

  public static function getKey($key) {
    return (is_null(\Drupal::service('key.repository')->getKey($key))) ? "" :
        \Drupal::service('key.repository')->getKey($key)->getKeyValue();
  }

}