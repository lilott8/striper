<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/21/17
 * Time: 12:02 PM
 */

namespace Drupal\striper\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\NumericFormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\striper\StriperStripeAPI;
use Drupal\striper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;

/**
 * Plugin implementation of the 'striper_plan_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "striper_plan_formatter",
 *   label = @Translation("Striper Plan"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class AppPlanFormatter extends EntityReferenceFormatterBase {

    protected $stripeApi;

    public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings) {
        $this->stripeApi = new StriperStripeAPI();
        parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    }

    /**
     * {@inheritdoc}
     */
    public static function defaultSettings() {
        return [
                // Implement default settings.
            ] + parent::defaultSettings();
    }

    protected function numberFormat($number) {
        // TODO: Implement numberFormat() method.
    }


    /**
     * {@inheritdoc}
     */
    public function settingsForm(array $form, FormStateInterface $form_state) {
        return [
                // Implement settings form.
            ] + parent::settingsForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function settingsSummary() {
        $summary = [];
        // Implement settings summary.
        return $summary;
    }

    public function viewElements(FieldItemListInterface $items, $langcode) {
        $elements = [];
        $current_path = Url::fromRoute('<current>')->getInternalPath();

        if (empty($this->stripeApi->publicKey)) {
            \Drupal::logger('striper')->critical('No Stripe API keys found');
            drupal_set_message($this->t("There was an unrecoverable error, please try again later."), 'error');
            return new RedirectResponse(Url::fromRoute('<front>'));
        }

        foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
            $plan = \Drupal::entityTypeManager()->getStorage('striper_plan')->load($entity->id());

            $elements[$delta] = array(
                '#theme' => 'striper_plan_format',
                '#price' => $plan->plan_price/ 100,
                '#plan_name' => $entity->id(),
                '#logged_in' => \Drupal::currentUser()->isAuthenticated(),
                '#data' => array(
                    // Price is specified in cents.
                    'amount' => $plan->plan_price,
                    'email' => \Drupal::currentUser()->getEmail(),
                    'key' => $this->stripeApi->publicKey,
                    'locale' => 'auto',
                    'label' => $this->t('Subscribe!'),
                ),
                "#anon_url" => Url::fromRoute('user.register', [], array(
                    'query' => array(
                        'destination' => $current_path,
                        'striper_checkout_click' => TRUE,
                    ),
                )),
                '#action' => Url::fromRoute('striper.app.plans.charge'),
                '#attached' => array(
                    'library' => array('striper/checkout',),
                ),
                '#account' => $entity,
                '#link_options' => array('attributes' => array('rel' => 'author')),
                '#cache' => array(
                    'tags' => $entity->getCacheTags(),
                ),
            );

            $portal_config = \Drupal::config('striper.config.portal');
            if($portal_config->get('company_name')) {
                $elements[$delta]['#data']['company_name'] = \Drupal::config('system.site')->get('name');
            }
            if($portal_config->get('company_image')) {
                $elements[$delta]['#data']['company_image'] = "https://stripe.com/img/documentation/checkout/marketplace.png";
            }
            if($portal_config->get('charge_description')) {
                $elements[$delta]['#data']['charge_description'] = \Drupal::config('system.site')->get('name') . " subscription";
            }
            if($portal_config->get('zip_code')) {
                $elements[$delta]['#data']['zip_code'] = 'true';
            }
            if($portal_config->get('billing_address')) {
                $elements[$delta]['#data']['billing_address'] = 'true';
            }
            if($portal_config->get('shipping_address')) {
                $elements[$delta]['#data']['shipping_address'] = 'true';
            }
            if($portal_config->get('email_address')) {
                $elements[$delta]['#data']['email_address'] = \Drupal::currentUser()->getEmail();
            }
            if($portal_config->get('remember_me')) {
                $elements[$delta]['#data']['remember_me'] = 'true';
            }
        }

        return $elements;
    }

    /**
     * Generate the output appropriate for one field item.
     *
     * @param \Drupal\Core\Field\FieldItemInterface $item
     *   One field item.
     *
     * @return string
     *   The textual output generated.
     */
    protected function viewValue(FieldItemInterface $item) {
        // The text value has no text format assigned to it, so the user input
        // should equal the output, including newlines.
        return nl2br(Html::escape($item->value));
    }
}