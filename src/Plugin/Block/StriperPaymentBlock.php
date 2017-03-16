<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/8/17
 * Time: 9:47 PM
 */

namespace Drupal\striper\Plugin\Block;


use Drupal\block\BlockForm;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\striper;

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
        return \Drupal::formBuilder()->getForm('\Drupal\striper\Form\AppPlanSelectForm');
        //return NULL;
    }

    public function defaultConfiguration() {
        return array(
            'Striper Sign-up Block' => $this->t('Allow users to sign up for your service.'),
        );
    }
}