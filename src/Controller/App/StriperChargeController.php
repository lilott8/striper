<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/21/17
 * Time: 11:43 AM
 */

namespace Drupal\striper\Controller\App;

use Drupal\striper;
use Drupal\Core\Controller\ControllerBase;
use Stripe\Charge;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;


class StriperChargeController extends ControllerBase {

    /**
     * Drupal\stripe_api\StripeApiService definition.
     *
     * @var \Drupal\striper\StriperStripeAPI
     */
    protected $striperApi;

    /**
     * {@inheritdoc}
     */
    public function __construct() {
        $this->striperApi = new striper\StriperStripeAPI();
    }

    /**
     * Charge.
     *
     * @return string
     *   Return Hello string.
     */
    public function charge(Request $request) {

        try {
            $token = $request->get('stripeToken');
            $config = \Drupal::configFactory()->get('striper.striper_plan.'.$request->get('plan_name'));

            if (!$token || is_null($config) || empty($config)) {
                throw new \Exception("Required data is missing!");
            }

            // Load the price from the config that was purchased.
            $amount = $config->get('plan_price');

            $user = \Drupal::currentUser()->getAccount();

            $charge = Charge::create(array(
                                         // This is stored in cents in config.
                                         "amount" => $amount,
                                         "source" => $token,
                                         "description" => $this->t('Purchase of @title', ['@title' => $config->get('plan_name')]),
                                         'currency' => 'usd',
                                         "metadata" => [
                                             'plan' => $request->get('plan_name'),
                                             'email' => $user->getEmail(),
                                             'uid' => $user->id(),
                                         ],
                                     ));

            \Drupal::logger('striper')->warning(striper\StriperDebug::vd($charge));

            if ($charge->paid === TRUE) {
                drupal_set_message(t("Thank you. Your payment has been processed."));

                \Stripe\Subscription::create(
                    array(
                        "customer" => $charge->customer,
                        "plan" => $request->get('plan_name'),
                    )
                );

                // At this point a Stripe webhook should make a request to the stripe_api.webhook route, which will dispatch an event
                // to which event subscribers can react.
                \Drupal::logger('striper')->info(t("Successfully processed Stripe charge. This should have triggered a Stripe webhook. \nsubmitted data:@data", [
                    '@data' => $request->getContent(),
                ]));

                // In addition to the webhook, we fire a traditional Drupal hook to permit other modules to respond to this event instantaneously.
                $this->moduleHandler()->invokeAll('stripe_checkout_charge_succeeded', [
                    $charge,
                    $config,
                    $user,
                ]);

                //return $this->redirect('striper.app.user.subscriptions');
                return array();
            }
            else {
                drupal_set_message(t("Payment failed."), 'error');

                \Drupal::logger('striper')->error(t("The charge was incomplete. \nsubmitted data:@data", [
                    '@data' => $request->getContent(),
                ]));

                //return $this->redirect('striper.app.user.subscriptions');
                return array();
            }

        }
        catch (\Exception $e) {
            drupal_set_message(t("Payment failed."), 'error');

            \Drupal::logger('striper')->error(t("Could not complete Stripe charge, error:\n@error\nsubmitted data:@data", [
                '@data' => $request->getContent(),
                '@error' => $e->getMessage(),
            ]));
            //return $this->redirect('striper.app.user.subscriptions');
            return array();
        }
    }

}