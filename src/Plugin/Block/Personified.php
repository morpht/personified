<?php

namespace Drupal\personified\Plugin\Block;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Personified' block.
 *
 * @Block(
 *   id = "personified",
 *   admin_label = @Translation("Personified"),
 *   category = @Translation("Search")
 * )
 */
class Personified extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Personified template entity storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $templateStorage;

  /**
   * Constructs a new Personified instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $template_storage
   *   The Personified template entity storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigEntityStorageInterface $template_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->templateStorage = $template_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('personified_template')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'endpoint' => '',
      'template' => '',
      'params' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    // Get all defined templates.
    $template_options = [];
    foreach ($this->templateStorage->loadMultiple() as $template) {
      $template_options[$template->id()] = $template->label();
    }

    // Prepare description links.
    $templates_link = Link::createFromRoute('Manage templates', 'entity.personified_template.collection')
      ->toString();

    $form['endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint'),
      '#description' => $this->t('The endpoint URL.'),
      '#default_value' => $config['endpoint'],
      '#required' => TRUE,
    ];
    $form['template'] = [
      '#type' => 'select',
      '#title' => $this->t('Template'),
      '#description' => $this->t('The template to use to display the results. @link.', [
        '@link' => $templates_link,
      ]),
      '#options' => $template_options,
      '#default_value' => $config['template'],
      '#required' => TRUE,
    ];
    $form['params'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Parameters'),
      '#description' => $this->t('The parameters to pass in the request.'),
    ];
    $form['params']['wrapper'] = [
      '#prefix' => '<div id="personified-params-wrapper">',
      '#suffix' => '</div>',
      '#process' => [[$this, 'paramsProcessCallback']],
    ];
    $form['params']['add_more'] = [
      '#type' => 'button',
      '#name' => 'personified_params_add_more',
      '#value' => t('Add another item'),
      '#attributes' => ['class' => ['field-add-more-submit']],
      '#ajax' => [
        'callback' => [$this, 'addMoreCallback'],
        'wrapper' => 'personified-params-wrapper',
        'effect' => 'fade',
      ],
      '#limit_validation_errors' => [],
    ];
    return $form;
  }

  /**
   * Render API callback: builds the params elements.
   */
  public function paramsProcessCallback(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // Use the processed values, if available.
    $params = NestedArray::getValue($form_state->getValues(), $element['#parents']);
    if (!$params) {
      // Next check the raw user input.
      $params = NestedArray::getValue($form_state->getUserInput(), $element['#parents']);
      if (!$params) {
        // If no user input exists, use the default values.
        $params = $this->getConfiguration()['params'];
      }
    }
    $config = [
      'source_type' => '',
      'source_key' => '',
      'endpoint_key' => '',
      'default_value' => '',
    ];

    if (empty($params)) {
      $params = [$config];
    }
    foreach ($params as $delta => $param) {
      if (isset($param['source_type'])) {
        $element[$delta] = $this->getParamElement($param);
      }
    }

    if (($input = $form_state->getUserInput()) && isset($input['_triggering_element_name'])
      && $input['_triggering_element_name'] === 'personified_params_add_more'
    ) {
      $delta++;
      $element[$delta] = self::getParamElement($config);
      $element[$delta]['#prefix'] = '<div class="ajax-new-content">';
      $element[$delta]['#suffix'] = '</div>';
    }
    return $element;
  }

  /**
   * Get single param element.
   *
   * @param $config
   *   The param config.
   *
   * @return array
   *   The param element.
   */
  private static function getParamElement($config) {
    $element = [
      '#type' => 'container',
      '#attributes' => ['class' => ['container-inline']],
    ];
    $element['source_type'] = [
      '#type' => 'select',
      '#title' => t('Source type'),
      '#options' => [
        'query' => t('Querystring'),
        'cookie' => t('Cookie'),
        'local_storage' => t('Local storage'),
        'data_layer' => t('Data layer'),
        'window' => t('Window'),
        'constant' => t('Constant'),
      ],
      '#default_value' => $config['source_type'],
    ];
    $element['source_key'] = [
      '#type' => 'textfield',
      '#title' => t('Source key'),
      '#default_value' => $config['source_key'],
      '#size' => 20,
    ];
    $element['endpoint_key'] = [
      '#type' => 'textfield',
      '#title' => t('Endpoint key'),
      '#default_value' => $config['endpoint_key'],
      '#size' => 20,
    ];
    $element['default_value'] = [
      '#type' => 'textfield',
      '#title' => t('Default value'),
      '#default_value' => $config['default_value'],
      '#size' => 20,
    ];
    return $element;
  }

  /**
   * Ajax callback for the "Add another item" button.
   */
  public static function addMoreCallback(array $form, FormStateInterface $form_state) {
    return $form['settings']['params']['wrapper'];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $params = [];
    foreach ($form_state->getValue('params')['wrapper'] as $param) {
      if (!empty($param['endpoint_key'])) {
        $params[] = $param;
      }
    }
    $this->configuration['endpoint'] = trim($form_state->getValue('endpoint'));
    $this->configuration['template'] = $form_state->getValue('template');
    $this->configuration['params'] = $params;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\personified\PersonifiedTemplateInterface $template */
    $template = $this->templateStorage->load($this->configuration['template']);
    if (empty($template)) {
      return [
        '#type' => 'markup',
        '#markup' => $this->t('Template "@id" was not found.', [
          '@id' => $this->configuration['template'],
        ]),
      ];
    }
    return [
      '#theme' => 'personified',
      '#endpoint' => $this->configuration['endpoint'],
      '#template' => $this->configuration['template'],
      '#params' => $this->configuration['params'],
      '#attached' => [
        'drupalSettings' => [
          'personifiedTemplate' => [
            $this->configuration['template'] => $template->getMarkup(),
          ],
        ],
      ],
    ];
  }

}
