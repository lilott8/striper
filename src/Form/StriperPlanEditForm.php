<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/9/17
 * Time: 3:27 PM
 */

namespace Drupal\striper\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class StriperPlanEditForm.
 *
 * Provides the edit form for our Striper_plan entity.
 *
 * @package Drupal\striper\Form
 *
 * @ingroup entity.striper_plan
 */

class StriperPlanEditForm extends StriperPlanFormBase {
    /**
     * Returns the actions provided by this form.
     *
     * For the edit form, we only need to change the text of the submit button.
     *
     * @param array $form
     *   An associative array containing the structure of the form.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   An associative array containing the current state of the form.
     *
     * @return array
     *   An array of supported actions for the current entity form.
     */
    protected function actions(array $form, FormStateInterface $form_state) {
        $actions = parent::actions($form, $form_state);
        $actions['submit']['#value'] = $this->t('Update Plan');
        return $actions;
    }
}