<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/15/17
 * Time: 4:36 PM
 */

namespace Drupal\striper\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class AppPlanSelectForm extends FormBase {
    public function getFormId() {
        // TODO: Implement getFormId() method.
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config("striper.striper_plan");


        $plans = array();

        $form['plans'] = array(
            '#title' => $this->t('Select Plan: '),
            '#type' => 'select',
            '#options' => $plans,
            '#required' => TRUE,
        );
        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Subscribe!'),
        );
        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        // TODO: Implement submitForm() method.
    }


}