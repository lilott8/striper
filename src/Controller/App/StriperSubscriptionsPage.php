<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/15/17
 * Time: 4:07 PM
 */

namespace Drupal\striper\Controller\App;


use Drupal\Core\Controller\ControllerBase;

class StriperSubscriptionsPage extends ControllerBase {

    public function viewPlans() {
        $page = $this->getTrendyHtmlPlans();

        $form = \Drupal::formBuilder()->getForm('Drupal\striper\Form\AppPlanSelectForm');

        \Drupal::logger('striper')->notice(print_r($form, 1));

        return array(
            'layout_control' => $form,
            '#content' => $page,
            '#type' => 'markup',
            '#title' => 'hi',
        );
    }

    private function getTrendyHtmlPlans() {
        return "here";
    }
}