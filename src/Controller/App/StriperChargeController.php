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
    public function __construct(striper\StriperStripeAPI $striperApi) {
        $this->striperApi = $striperApi;
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
            $entity_id = $request->get('entity_id');
            $field_name = $request->get('field_name');

            if (!$token || !$entity_id || !$field_name) {
                throw new \Exception("Required data is missing!");
            }

            // Load the price from the node that was purchased.
            $node = $this->entityTypeManager()->getStorage('node')->load($entity_id);
            $amount = $node->$field_name->value;
            $field_settings = $node->$field_name->getSettings();
            $currency = $field_settings['currency'];
            $user = \Drupal::currentUser()->getAccount();

            $charge = Charge::create(array(
                                         // Convert to cents.
                                         "amount" => $amount * 100,
                                         "source" => $token,
                                         "description" => $this->t('Purchase of @title', ['@title' => $node->label()]),
                                         'email' => $user->getEmail(),
                                         'currency' => $currency,
                                         "metadata" => [
                                             'entity_id' => $entity_id,
                                             'uid' => $user->id(),
                                         ],
                                     ));

            if ($charge->paid === TRUE) {
                drupal_set_message(t("Thank you. Your payment has been processed."));

                // At this point a Stripe webhook should make a request to the stripe_api.webhook route, which will dispatch an event
                // to which event subscribers can react.
                $this->getLogger('stripe_checkout.logger')->info(t("Successfully processed Stripe charge. This should have triggered a Stripe webhook. \nsubmitted data:@data", [
                    '@data' => $request->getContent(),
                ]));

                // In addition to the webhook, we fire a traditional Drupal hook to permit other modules to respond to this event instantaneously.
                $this->moduleHandler()->invokeAll('stripe_checkout_charge_succeeded', [
                    $charge,
                    $node,
                    $user,
                ]);

                return $this->redirect('entity.node.canonical', array('node' => $entity_id));
            }
            else {
                drupal_set_message(t("Payment failed."), 'error');

                $this->getLogger('stripe_checkout.logger')->error(t("Could not complete Stripe charge. \nsubmitted data:@data", [
                    '@data' => $request->getContent(),
                ]));

                return new Response(NULL, Response::HTTP_FORBIDDEN);
            }

        }
        catch (\Exception $e) {
            drupal_set_message(t("Payment failed."), 'error');

            $this->getLogger('striper.logger')->error(t("Could not complete Stripe charge, error:\n@error\nsubmitted data:@data", [
                '@data' => $request->getContent(),
                '@error' => $e->getMessage(),
            ]));
            return new Response(NULL, Response::HTTP_FORBIDDEN);
        }
    }

}