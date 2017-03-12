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
    protected function getEditableConfigNames() {
        // TODO: Implement getEditableConfigNames() method.
    }

    public function getFormId() {
        // TODO: Implement getFormId() method.
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

        $form['company_name'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Company Name'),
            '#description' => $this->t("Display the company's name."),
            '#default_value' => '',
        );

        $form['charge_description'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Description of charge'),
            '#description' => $this->t('Display the description of the charge.'),
            '#default_value' => '',
        );

        $form['charge_amount'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Charge Amount'),
            '#description' => $this->t('Dispaly the charge amount.'),
            '#default_value' => '',
        );

        // TODO: denote in the form these are very important from Stripe, default to TRUE
        $form['zip_code'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Zip Code'),
            '#description' => $this->t('Require the user to provide a zip code.'),
            '#default_value' => '',
        );

        // TODO: denote in the form these are very important from Stripe, default to TRUE
        $form['billing_address'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Billing Address'),
            '#description' => $this->t('Require the user to provide a billing address.'),
            '#default_value' => '',
        );

        $form['shipping_address'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Shipping Address'),
            '#description' => $this->t('Require the user to provide a shipping address.'),
            '#default_value' => '',
        );

        // TODO: denote in the form these are very important from Stripe, default to TRUE
        $form['email_address'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Email Address'),
            '#description' => $this->t('Submit an email address with the payment.'),
            '#default_value' => '',
        );

        $form['remember_me'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Remember Me'),
            '#description' => $this->t('Let the user allow Stripe to remember them.'),
            '#default_value' => '',
        );

        return parent::buildForm($form, $form_state);
    }


}