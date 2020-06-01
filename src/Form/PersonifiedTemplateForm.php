<?php

namespace Drupal\personified\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides the Personified template entity form.
 */
class PersonifiedTemplateForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // Get existing or default markup value.
    $markup = $this->entity->getMarkup();
    if (empty($markup)) {
      $markup = '<ul>{{#each items}}<li>{{title}}</li>{{/each}}</ul>';
    }

    // Prepare description links.
    $handlebars_link = Link::fromTextAndUrl('documentation', Url::fromUri('https://handlebarsjs.com/guide/'))
      ->toString();

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => 'Name',
      '#description' => t('The name of the template.'),
      '#default_value' => $this->entity->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
      '#disabled' => !$this->entity->isNew(),
    ];
    $form['markup'] = [
      '#type' => 'textarea',
      '#title' => t('Template markup'),
      '#description' => $this->t('The Handlebars template markup (see @link). Use "{{log this}}" to debug inside console.', [
        '@link' => $handlebars_link,
      ]),
      '#default_value' => $markup,
      '#required' => TRUE,
      '#rows' => 20,
    ];
    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    if ($this->entity->isNew()) {
      $actions['submit']['#value'] = t('Add template');
    }
    else {
      $actions['submit']['#value'] = t('Save template');
    }
    return $actions;
  }

  /**
   * Determines if the entity already exists.
   *
   * @param string|int $entity_id
   *   The entity ID.
   * @param array $element
   *   The form element.
   *
   * @return bool
   *   TRUE if the entity exists, FALSE otherwise.
   */
  public function exists($entity_id, array $element) {
    return (bool) $this->entityTypeManager->getStorage('personified_template')
      ->getQuery()->condition('id', $entity_id)->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    if ($this->entity->save() === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Personified template created.'));
    }
    else {
      $this->messenger()->addStatus($this->t('Personified template updated.'));
    }
    $form_state->setRedirect('entity.personified_template.collection');
  }

}
