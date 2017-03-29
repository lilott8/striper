<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/7/17
 * Time: 8:12 PM
 */

namespace Drupal\striper\Controller\App;

use Drupal\Core\Controller\ControllerBase;
use Drupal\striper\StriperStripeAPI;
use Stripe\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends ControllerBase {

    private $stripe;

    public function __construct() {
        $stripe = new StriperStripeAPI();
    }

    /**
     * @param Request $request
     *
     * This will do a few things:
     * validate user subscriptions on auto pay,
     * remove users who have cancelled,
     * email users on failed subscriptions
     *
     * @return int
     */
    public function handler(Request $request) {
        \Drupal::logger('striper')->alert("sanity check %num", array('%num'=> rand(0,100)));
        $contents = $request->getContent();

        if(!$contents) {
            \Drupal::logger('striper')->warning('No content in request from Stripe.');
            return Response::HTTP_BAD_REQUEST;
        }

        try {
            $event = \GuzzleHttp\json_decode($contents);
            //\Drupal::logger('striper')->warning($event);
            $type = $event->type;
            \Drupal::logger('striper')->warning($type);
        } catch (\Exception $e) {
            \Drupal::logger('striper')->warning($e->getMessage());
        }

        return array();
    }
}