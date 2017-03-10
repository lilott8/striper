<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 3/9/17
 * Time: 1:30 PM
 */

namespace Drupal\striper\Controller;


use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

class StriperListBuilder extends ConfigEntityListBuilder {

    /**
     * Builds the header row for the entity listing.
     *
     * @return array
     *   A render array structure of header strings.
     *
     * @see Drupal\Core\Entity\EntityListController::render()
     */
    public function  buildHeader() {
        $header['plan_name'] = $this->t('Plan Name');
        $header['machine_name'] = $this->t('Machine Name');
        $header['price'] = $this->t('Cost');
        $header['frequency'] = $this->t('Frequency');
        $header['active_plan'] = $this->t('Active');
        $header['plan_in_striper'] = $this->t('From Stripe');
        return $header + parent::buildHeader();
    }

    /**
     * Builds a row for an entity in the entity listing.
     *
     * @param EntityInterface $entity
     *   The entity for which to build the row.
     *
     * @return array
     *   A render array of the table row for displaying the entity.
     *
     * @see Drupal\Core\Entity\EntityListController::render()
     */
    public function buildRow(EntityInterface $entity) {
        $row['plan_name'] = $entity->planName;
        $row['machine_name'] = $entity->id();
        $row['price'] = $entity->planPrice;
        $row['frequency'] = $entity->planFrequency;
        $row['active_plan'] = $entity->planActive;
        $row['plan_in_stripe'] = $entity->planInStripe;

        return $row + parent::buildRow($entity); // TODO: Change the autogenerated stub
    }

}