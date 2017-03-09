<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/8/17
 * Time: 9:47 PM
 */

namespace Drupal\striper\Plugin\Block;


use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Striper' Block.
 *
 * @Block(
 *   id = "striper_payment_block",
 *   admin_label = @Translation("Striper payment block"),
 * )
 */

class StriperPaymentBlock extends BlockBase {
    public function build() {
        // TODO: Implement build() method.
        return array();
    }

    public function defaultConfiguration() {
        return array(
            'block_example_string' => $this->t('A default value. This block was created at %time', array('%time' => date('c'))),
        );
    }

    public function blockForm($form, FormStateInterface $form_state) {
        return parent::blockForm($form, $form_state); // TODO: Change the autogenerated stub
    }
}