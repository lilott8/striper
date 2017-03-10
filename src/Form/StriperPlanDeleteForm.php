<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/9/17
 * Time: 3:28 PM
 */

namespace Drupal\striper\Form;


use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class StriperPlanDeleteForm extends EntityConfirmFormBase {
    public function getQuestion() {
        return $this->t('Are you sure you want to delete plan %plan', array('%plan', $this->entity->planName));
    }

    public function getCancelUrl() {
        return new Url('entity,striper.plans.list');
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        if($this->entity->planInStripe) {
            $this->entity->planActive = FALSE;
            $this->entity->save();
            $message = 'Plan %plan is from Stripe, the plan is being deactivated.';
        } else {
            $this->entity->delete();
            $message = 'Plan %plan was deleted.';
        }

        drupal_set_message($message, array('%plan', $this->entity->planName));

        $form_state->setRedirectUrl($this->getCancelUrl());
    }

}