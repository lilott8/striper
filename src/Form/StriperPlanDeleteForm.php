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

/**
 * Class StriperPlanDeleteForm.
 *
 * Provides a confirm form for deleting the entity. This is different from the
 * add and edit forms as it does not inherit from StriperPlanFormBase. The reason for
 * this is that we do not need to build the same form. Instead, we present the
 * user with a simple yes/no question. For this reason, we derive from
 * EntityConfirmFormBase instead.
 *
 * @package Drupal\striper\Form
 *
 * @ingroup entity.striper_plan
 */

class StriperPlanDeleteForm extends EntityConfirmFormBase {
    public function getQuestion() {
        return $this->t('Are you sure you want to delete plan %plan', array('%plan', $this->entity->label()));
    }

    public function getCancelUrl() {
        return new Url('entity.striper_plan.list');
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