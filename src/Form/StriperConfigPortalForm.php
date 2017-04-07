<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/12/17
 * Time: 12:53 PM
 */

namespace Drupal\striper\Form;


use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class StriperConfigPortalForm extends ConfigFormBase {

    const STRIPE_MAP = array(
        'company_name' => 'data-name',
        'company_image' => 'data-image',
        'charge_description' => 'data-description',
        'charge_amount' => 'data-amount',
        'zip_code' => 'data-zip-code',
        'billing_address' => 'data-billing-address',
        'shipping_address' => 'data-shipping-address',
        'email_address' => 'data-email',
        'remember_me' => 'data-allow-remember-me',
    );

    protected function getEditableConfigNames() {
        return ['striper.config.portal'];
    }

    public function getFormId() {
        return 'striper_admin_config_portal';
    }

    /**
     * Customization recommendations from: https://stripe.com/docs/checkout#integration-custom
     *
     * @param array              $form
     * @param FormStateInterface $form_state
     *
     * @return array
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $config = \Drupal::config('striper.config.portal');

        $form['company_name'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Company Name'),
            '#description' => $this->t("Display the company's name."),
            '#default_value' => $config->get('company_name'),
        );

        $form['company_image'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Company Image'),
            '#description' => $this->t("Display the company's image.  Automatically pulled from the site"),
            '#default_value' => $config->get('company_image'),
        );

        $form['charge_description'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Description of charge'),
            '#description' => $this->t('Display the description of the charge.'),
            '#default_value' => $config->get('charge_description'),
        );

        $form['charge_amount'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Charge Amount'),
            '#description' => $this->t('Dispaly the charge amount.'),
            '#default_value' => $config->get('charge_amount'),
        );

        // TODO: denote in the form these are very important from Stripe, default to TRUE
        $form['zip_code'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Zip Code'),
            '#description' => $this->t('Require the user to provide a zip code. [Stripe recommends collecting this.]'),
            '#default_value' => $config->get('zip_code'),
        );

        // TODO: denote in the form these are very important from Stripe, default to TRUE
        $form['billing_address'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Billing Address'),
            '#description' => $this->t('Require the user to provide a billing address. [Stripe recommends collecting this.]'),
            '#default_value' => $config->get('billing_address'),
        );

        $form['shipping_address'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Shipping Address'),
            '#description' => $this->t('Require the user to provide a shipping address.'),
            '#default_value' => $config->get('shipping_address'),
        );

        // TODO: denote in the form these are very important from Stripe, default to TRUE
        $form['email_address'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Email Address'),
            '#description' => $this->t('Submit an email address with the payment. [Stripe recommends collecting this.]'),
            '#default_value' => $config->get('email_address'),
        );

        $form['remember_me'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Remember Me'),
            '#description' => $this->t('Let the user allow Stripe to remember them.'),
            '#default_value' => $config->get('remember_me'),
        );

        return parent::buildForm($form, $form_state);
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {

        \Drupal::logger('striper')->notice('saving the settings');

        $config = \Drupal::service('config.factory')->getEditable('striper.config.portal');
        $config->set('company_name', $form_state->getValue('company_name'));
        $config->set('company_image', $form_state->getValue('company_image'));
        $config->set('charge_description', $form_state->getValue('charge_description'));
        $config->set('charge_amount', $form_state->getValue('charge_amount'));
        $config->set('zip_code', $form_state->getValue('zip_code'));
        $config->set('billing_address', $form_state->getValue('billing_address'));
        $config->set('shipping_address', $form_state->getValue('shipping_address'));
        $config->set('email_address', $form_state->getValue('email_address'));
        $config->set('remember_me', $form_state->getValue('remember_me'));

        $config->save();
        parent::submitForm($form, $form_state); // TODO: Change the autogenerated stub
    }


}