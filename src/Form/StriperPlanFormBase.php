<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/9/17
 * Time: 1:57 PM
 */

namespace Drupal\striper\Form;


use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;

/**
 * Class StriperPlanFormBase.
 *
 * Typically, we need to build the same form for both adding a new entity,
 * and editing an existing entity. Instead of duplicating our form code,
 * we create a base class. Drupal never routes to this class directly,
 * but instead through the child classes of StriperPlanAddForm and StriperEditForm.
 *
 * @package Drupal\striper\Form
 *
 * @ingroup entity.striper_plan
 */

class StriperPlanFormBase extends EntityForm {

    public function buildForm(array $form, FormStateInterface $form_state) {

        $plan = $this->entity;

        if($this->operation == 'edit') {
            $form['#title'] = $this->t('Edit Plan: @name', array('@name' => $plan->planName));
        }

        // only edit plans not in Stripe
        $editable = !is_null($plan->planInStripe) ? $plan->planInStripe : FALSE;
        $source = $editable ? 'stripe' : 'drupal';
        $price = $plan->planInStripe ? $plan->planPrice : 0;
        $stripeId = $plan->planInStripe ? $plan->planStripeId : "-1";

        $form['plan_name'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Name'),
            '#maxlength' => 255,
            '#default_value' => $plan->plan_name,
            '#disabled' => $editable,
            '#required' => TRUE,
        );

        $form['id'] = array(
            '#type' => 'machine_name',
            '#title' => $this->t('Machine name'),
            '#default_value' => $plan->id(),
            '#machine_name' => array(
                'exists' => array($this, 'exists'),
                'replace_pattern' => '([^a-z0-9_]+)|(^custom$)',
                'source' => array('plan_name'),
                'error' => 'The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores. Additionally, it can not be the reserved word "custom".',
            ),
            '#disabled' => !$plan->isNew(),
        );

        $form['plan_price'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Price'),
            '#maxlength' => 255,
            '#default_value' => $price,
            '#disabled' => $editable,
        );

        $form['plan_stripe_id'] = array(
            '#type' => 'hidden',
            '#title' => $this->t(''),
            '#maxlength' => 255,
            '#default_value' => $stripeId,
            '#disabled' => TRUE,
            '#attributes' => array('#readonly'=>'readonly'),
        );

        $form['plan_frequency'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Frequency'),
            '#maxlength' => 255,
            '#default_value' => $plan->plan_frequency,
            '#disabled' => $editable,
        );

        $form['plan_active'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Enable this plan'),
            '#default_value' => $plan->plan_active,
        );

        $form['plan_source'] = array(
            '#type' => 'hidden',
            '#title' => t('Plan Source'),
            '#default_value' => $source,
            '#disabled' => !$editable,
            '#attributes' => array('#readonly' =>'readonly'),
        );

        return parent::buildForm($form, $form_state);
    }

    protected function actions(array $form, FormStateInterface $form_state) {
        $actions = parent::actions($form, $form_state);

        $actions['submit']['#value'] = $this->t('Save');

        return $actions;
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
        parent::validateForm($form, $form_state); // TODO: Change the autogenerated stub
    }

    public function exists($entity_id, array $element, FormStateInterface $form_state) {
        // Use the query factory to build a new robot entity query.
        $query = \Drupal::entityQuery('striper_plan');

        // Query the entity ID to see if its in use.
        $result = $query->condition('id', $element['#field_prefix'] . $entity_id)->execute();

        // We don't need to return the ID, only if it exists or not.
        return (bool) $result;
    }

    public function save(array $form, FormStateInterface $form_state) {
        $plan = $this->getEntity();
        $status = $plan->save();

        $url = $plan->toUrl();
        $editLink = Link::fromTextAndUrl($this->t('Edit'), $url);

        if($status == SAVED_UPDATED) {
            // If we edited an existing entity...
            drupal_set_message($this->t('Plan %name has been updated.', array('%name' => $plan->planName)));
            $this->logger('contact')->notice('Plan %name has been updated.', ['%name' => $plan->planName]);
        } else {
            // If we created a new entity...
            drupal_set_message($this->t('Plan %name has been added.', array('%name' => $plan->planName)));
            $this->logger('contact')->notice('Plan %name has been added.', ['%name' => $plan->planName]);
        }
        $form_state->setRedirect('entity.striper_plan.list');
    }


}