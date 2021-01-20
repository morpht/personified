<?php

namespace Drupal\personified\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\json_template\Plugin\JsonTransformerManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Personified Message' block.
 *
 * @Block(
 *   id = "personified_message",
 *   admin_label = @Translation("Personified Message"),
 *   category = @Translation("Search")
 * )
 */
class PersonifiedMessage extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The JSON transformer manager.
   *
   * @var \Drupal\json_template\Plugin\JsonTransformerManagerInterface
   */
  protected $jsonTransformer;

  /**
   * Constructs a new PersonifiedMessage instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\json_template\Plugin\JsonTransformerManagerInterface $json_transformer
   *   The JSON transformer manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, JsonTransformerManagerInterface $json_transformer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->jsonTransformer = $json_transformer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.json_template.transformer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'template' => '',
      'transformer' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    // Get all defined transformers.
    $transformer_options = [];
    foreach ($this->jsonTransformer->getDefinitions() as $transformer) {
      $transformer_options[$transformer['id']] = $transformer['title'];
    }

    $form['template'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#description' => $this->t('The message markup to be transformed.'),
      '#default_value' => $config['template'],
      '#required' => TRUE,
    ];
    $form['transformer'] = [
      '#type' => 'select',
      '#title' => $this->t('Transformer'),
      '#description' => $this->t('The transformer to use to display the results.'),
      '#options' => $transformer_options,
      '#default_value' => $config['transformer'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['template'] = trim($form_state->getValue('template'));
    $this->configuration['transformer'] = $form_state->getValue('transformer');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (!$this->jsonTransformer->hasDefinition($this->configuration['transformer'])) {
      return [
        '#type' => 'markup',
        '#markup' => $this->t('Transformer "@id" was not found.', [
          '@id' => $this->configuration['transformer'],
        ]),
      ];
    }
    $definition = $this->jsonTransformer->getDefinition($this->configuration['transformer']);
    return [
      '#theme' => 'personified_message',
      '#template' => $this->configuration['template'],
      '#transformer' => $this->configuration['transformer'],
      '#attached' => ['library' => [$definition['library']]],
    ];
  }

}
