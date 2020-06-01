<?php

namespace Drupal\personified\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\personified\PersonifiedTemplateInterface;

/**
 * Defines the Personified template configuration entity.
 *
 * @ConfigEntityType(
 *   id = "personified_template",
 *   label = @Translation("Personified template"),
 *   label_collection = @Translation("Personified templates"),
 *   label_singular = @Translation("Personified template"),
 *   label_plural = @Translation("Personified templates"),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\personified\Form\PersonifiedTemplateForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\personified\PersonifiedTemplateListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer personified templates",
 *   config_prefix = "template",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "markup",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/block/personified-templates",
 *     "add-form" = "/admin/structure/block/personified-templates/add",
 *     "edit-form" = "/admin/structure/block/personified-templates/{personified_template}/edit",
 *     "delete-form" = "/admin/structure/block/personified-templates/{personified_template}/delete",
 *   },
 * )
 */
class PersonifiedTemplate extends ConfigEntityBase implements PersonifiedTemplateInterface {

  /**
   * The Personified template machine name.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the Personified template entity.
   *
   * @var string
   */
  protected $label;

  /**
   * The Personified template markup.
   *
   * @var string
   */
  protected $markup;

  /**
   * {@inheritdoc}
   */
  public function getMarkup() {
    return $this->markup;
  }

  /**
   * {@inheritdoc}
   */
  public function setMarkup($markup) {
    $this->markup = $markup;
    return $this;
  }

}
