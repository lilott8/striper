<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/9/17
 * Time: 1:08 PM
 */

namespace Drupal\striper\Entity;


use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\striper\StriperPlanInterface;

/**
 * Defines the stripe plan entity
 *
 * @ingroup config_entity_example
 *
 * This is the main definition of the entity type. From it, an entityType is
 * derived. The most important properties in this example are listed below.
 *
 * id: The unique identifier of this entityType. It follows the pattern
 * 'moduleName_xyz' to avoid naming conflicts.
 *
 * label: Human readable name of the entity type.
 *
 * handlers: Handler classes are used for different tasks. You can use
 * standard handlers provided by D8 or build your own, most probably derived
 * from the standard class. In detail:
 *
 * - view_builder: we use the standard controller to view an instance. It is
 *   called when a route lists an '_entity_view' default for the entityType
 *   (see routing.yml for details. The view can be manipulated by using the
 *   standard drupal tools in the settings.
 *
 * - list_builder: We derive our own list builder class from the
 *   entityListBuilder to control the presentation.
 *   If there is a view available for this entity from the views module, it
 *   overrides the list builder. @todo: any view? naming convention?
 *
 * - form: We derive our own forms to add functionality like additional fields,
 *   redirects etc. These forms are called when the routing list an
 *   '_entity_form' default for the entityType. Depending on the suffix
 *   (.add/.edit/.delete) in the route, the correct form is called.
 *
 * - access: Our own accessController where we determine access rights based on
 *   permissions.
 *
 * More properties:
 *
 *  - base_table: Define the name of the table used to store the data. Make sure
 *    it is unique. The schema is automatically determined from the
 *    BaseFieldDefinitions below. The table is automatically created during
 *    installation.
 *
 *  - fieldable: Can additional fields be added to the entity via the GUI?
 *    Analog to content types.
 *
 *  - entity_keys: How to access the fields. Analog to 'nid' or 'uid'.
 *
 *  - links: Provide links to do standard tasks. The 'edit-form' and
 *    'delete-form' links are added to the list built by the
 *    entityListController. They will show up as action buttons in an additional
 *    column.
 *
 * There are many more properties to be used in an entity type definition. For
 * a complete overview, please refer to the '\Drupal\Core\Entity\EntityType'
 * class definition.
 *
 * The following construct is the actual definition of the entity type which
 * is read and cached. Don't forget to clear cache after changes.
 *
 * @ConfigEntityType(
 *   id = "striper.config.plans",
 *   label = @Translation("Striper"),
 *   admin_permission = "administer striper",
 *   handlers = {
 *     "access" = "Drupal\striper\StriperAccessController",
 *     "list_builder" = "Drupal\striper\Controller\StriperListBuilder",
 *     "form" = {
 *       "add" = "Drupal\striper\Form\StriperPlanAddForm",
 *       "edit" = "Drupal\sriper\Form\StriperPlanEditForm",
 *       "delete" = "Drupal\striper\Form\StriperPlanDeleteForm"
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "planName"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/striper/plans/manage/{plan}",
 *     "delete-form" = "/admin/config/striper/plans/manage/{plan}/delete"
 *   }
 * )
 *
 * @package Drupal\striper\Entity
 */
class StriperPlanEntity extends ConfigEntityBase implements StriperPlanInterface {

    /**
     * The plan ID
     *
     * @var string
     */
    public $id;

    /**
     * plan uuid
     *
     * @var string
     */
    public $uuid;

    /**
     * Name of plan
     *
     * @var string
     */
    public $planName;

    /**
     * Cost of plan
     *
     * @var integer
     */
    public $planPrice;

    /**
     * The frequency with which the plan is charged
     *
     * @var string
     */
    public $planFrequency;

    /**
     * Denotes if the plan is active
     *
     * @var boolean
     */
    public $planActive;

    /**
     * Is this a custom plan -- to be used for free subscriptions
     *
     * @var boolean
     */
    public $planInStripe;

    /**
     * Stripe Plan ID
     *
     * @var string
     */
    public $planStripeId;
}