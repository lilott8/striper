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
 * Plugin implementation of the 'striper_plan_field_type' field type.
 *
 * @FieldType(
 *   id = "striper_plan",
 *   label = @Translation("Striper Plan"),
 *   description = @Translation("Striper Plan field. References a striper_plan config entity."),
 *   category = @Translation("Reference"),
 *   default_widget = "entity_reference_autocomplete",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 *   default_formatter = "striper_plan_formatter"
 * )
 */

class AppPlanFieldType extends EntityReferenceItem {

    /**
     * {@inheritdoc}
     */
    public static function defaultStorageSettings() {
        return array() + parent::defaultStorageSettings();
    }

    /**
     * {@inheritdoc}
     */
    public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
        return parent::storageSettingsForm($form, $form_state, $has_data);
    }

}