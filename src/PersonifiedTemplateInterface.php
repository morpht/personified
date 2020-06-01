<?php

namespace Drupal\personified;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a Personified template.
 */
interface PersonifiedTemplateInterface extends ConfigEntityInterface {

  /**
   * Gets the markup for this Personified template.
   *
   * @return string
   *   The template markup.
   */
  public function getMarkup();

  /**
   * Sets the markup for this Personified template.
   *
   * @param string $markup
   *   The template markup.
   *
   * @return $this
   */
  public function setMarkup($markup);

}
