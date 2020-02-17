<?php

namespace Drupal\cohesion_custom_styles\Plugin\Api;

use Drupal\cohesion\Entity\EntityJsonValuesInterface;
use Drupal\cohesion\StylesApiPluginBase;
use Drupal\cohesion_custom_styles\Entity\CustomStyle;

/**
 * Class CustomStylesApi.
 *
 * @package Drupal\cohesion_custom_styles
 *
 * @Api(
 *   id = "custom_styles_api",
 *   name = @Translation("Custom styles send to API"),
 * )
 */
class CustomStylesApi extends StylesApiPluginBase {

  /**
   * @var \Drupal\cohesion_custom_styles\Entity\CustomStyle*/
  protected $entity;

  /**
   * @var \Drupal\cohesion_custom_styles\Entity\CustomStyle*/
  protected $parent;

  /**
   * {@inheritdoc}
   */
  public function getForms() {
    // Hash the child entities.
    $child_entities = $this->parent->getChildEntities();
    $child_resources = NULL;

    if (count($child_entities)) {
      /** @var \Drupal\cohesion_custom_styles\Entity\CustomStyle $child */
      foreach ($child_entities as $child) {
        // Only process enable children.
        if ($child->getStatus()) {
          $resource = $child->getResourceObject();
          $this->processBackgroundImageInheritance($resource->values);

          $child_resources[] = $resource;
        }
      }
    }

    // Send the parent and children to the API.
    // processBackgroundImageInheritance.
    $resource = $this->parent->getResourceObject();
    $this->processBackgroundImageInheritance($resource->values);

    return [
      $this->getFormElement($resource, $child_resources),
    ];
  }

  public function setEntity(EntityJsonValuesInterface $entity) {
// TODO: Change the autogenerated stub
    parent::setEntity($entity);
    // Assume this entity is the parent.
    $this->parent = $this->entity;

    // If this is a child element, set the parent element.
    if ($parent_id = $this->entity->getParentId()) {
      $this->parent = \Drupal::entityTypeManager()
        ->getStorage('cohesion_custom_style')
        ->load($parent_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepareData($attach_css = TRUE) {
    parent::prepareData($attach_css);

    // Reorder custom style styles.
    $custom_styles = CustomStyle::loadParentChildrenOrdered();
    $style_order = [];
    if ($custom_styles) {
      foreach ($custom_styles as $custom_style) {
        $key = $custom_style->id() . '_' . $custom_style->getConfigItemId();
        $style_order[] = $key;
      }
    }

    $this->data->sort_order = $style_order;
    $this->data->style_group = 'cohesion_custom_style';
  }

  /**
   * {@inheritdoc}
   */
  public function send() {
    // Send to API only if the parent of this entity is enabled.
    if ($this->parent && $this->parent->status() || $this->getSaveData()) {
      return parent::send();
    }

    return TRUE;
  }

  /**
   * Remove the entity from stylesheet.json.
   *
   * @return bool|void
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function delete() {

    // Assume this entity is the parent.
    $this->parent = $this->entity;

    // If this is a child element, set the parent element.
    if ($parent_id = $this->entity->getParentId()) {
      $this->parent = \Drupal::entityTypeManager()
        ->getStorage('cohesion_custom_style')
        ->load($parent_id);
    }

    parent::delete();
  }

}