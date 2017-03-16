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
use Drupal\striper\StriperStripeAPI;
use Drupal\Core\Url;

class AppPlanSelectForm extends FormBase {
    public function getFormId() {
        return 'app_plan_select_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {

    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        // TODO: Implement submitForm() method.
        return $form_state->setRedirectUrl(Url::fromRoute('striper.app.subscriptions.list'));
    }

}