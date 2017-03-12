<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/11/17
 * Time: 10:01 PM
 */

namespace Drupal\striper\Controller;


use Drupal\Core\Controller\ControllerBase;
use \Symfony\Component\HttpFoundation\Response;

class StriperUserList extends ControllerBase {

    public function listUsers() {
        $response = new Response();
        $response->setContent('Hello ');
        return $response;
    }

}