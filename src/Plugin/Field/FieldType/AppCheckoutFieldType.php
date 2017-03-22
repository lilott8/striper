<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/21/17
 * Time: 12:01 PM
 */

namespace Drupal\striper\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\Plugin\Field\FieldType\DecimalItem;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'striper_checkout_field_type' field type.
 *
 * @FieldType(
 *   id = "striper_checkout",
 *   label = @Translation("Striper Plan"),
 *   description = @Translation("Striper Checkout field. References a striper_plan config entity."),
 *   category = @Translation("Reference"),
 *   default_widget = "entity_reference_autocomplete",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 *   default_formatter = "striper_checkout_formatter"
 * )
 */

class AppCheckoutFieldType extends EntityReferenceItem {

    /**
     * {@inheritdoc}
     */
    public static function defaultStorageSettings() {
        return array(

            ) + parent::defaultStorageSettings();
    }

    /**
     * {@inheritdoc}
     */
    public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
        return parent::storageSettingsForm($form, $form_state, $has_data);
        /**
        $element = parent::storageSettingsForm($form, $form_state, $has_data);
        $settings = $this->getSettings();

        $element['currency'] = array(
            '#type' => 'textfield',
            '#title' => t('Currency'),
            '#default_value' => $settings['currency'],
            '#length' => 3,
            '#size' => 3,
            '#description' => t('The three character ISO currency code for this price.'),
            '#disabled' => $has_data,
        );

        return $element;
         */
    }

}