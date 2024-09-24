<?php

namespace Drupal\nrfc_league_table_scraper\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure site information settings for this site.
 *
 * @internal
 */
class NrfcLeagueTableScraperForm extends ConfigFormBase
{
  const FORM_ID = 'nrfc_league_table_scraper';
  const SETTINGS_NAME = self::FORM_ID . '.scraper_settings';
  private EntityTypeManagerInterface $entityTypeManager;

  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    EntityTypeManagerInterface $entity_type_manager)
  {
    parent::__construct($config_factory, $typedConfigManager);
    $this->entityTypeManager = $entity_type_manager;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('entity_type.manager')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return self::FORM_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {

    $form['england_rfu_target'] = [
      '#type' => 'details',
      '#title' => $this->t('England RFU target'),
      '#open' => TRUE,
    ];

    $form['england_rfu_target']['base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#config_target' => self::SETTINGS_NAME . ':base_url',
      '#description' => $this->t(
        'The base URL without parameters for the teams search on the England RFU site.'
      ),
    ];

    $form['england_rfu_target']['xpath'] = [
      '#type' => 'textfield',
      '#title' => $this->t('XPATH'),
      '#config_target' => self::SETTINGS_NAME . ':xpath',
      '#description' => $this->t(
        'The xpath to the table rows to scrape. This will change each time the RFU change their UI.'
      ),
    ];

    $form['england_rfu_target']['fragment'] = [
      '#type' => 'textfield',
      '#title' => $this->t('fragment'),
      '#config_target' => self::SETTINGS_NAME . ':fragment',
      '#description' => $this->t(
        'The document fragment (#anchor) that ensures the table is visble, should not be needed.'
      ),
    ];

//    $nodes = \Drupal\node\Entity\Node::loadMultiple(
//      $this->entityTypeManager::entityQuery('node')
//        ->accessCheck(true)
//        ->condition('type', 'team')
//        ->execute()
//    );
    $query = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery();
    $nids = $query
      ->condition('type', 'team')
      ->accessCheck(TRUE)
      ->execute();
    $nodes = \Drupal\node\Entity\Node::loadMultiple($nids);

    // Sort nodes by Section (Taxonomy term) order
    usort($nodes, function ($a, $b) {
      return strcmp($this->getSectionSortName($b), $this->getSectionSortName($a));
    });

    foreach ($nodes as $node) {
      $teamNid = $node->id();
      $index = 'nid_' . $teamNid;
      $settingsPrefix = self::SETTINGS_NAME . ':' . $index . "_";

      $form[$index] = [
        '#type' => 'details',
        '#title' => $node->getTitle(),
        '#open' => FALSE,
      ];

      // https://www.englandrugby.com/fixtures-and-results/search-results?
      //team=15036&
      //competition=261&
      //division=56597&
      //season=2024-2025
      //#tables

      $form[$index]['teamId' . $teamNid] = [
        '#type' => 'textfield',
        '#config_target' => $settingsPrefix . 'teamId',
        '#title' => 'Team ID',
        '#description' => $settingsPrefix . 'teamId',
      ];
      $form[$index]['competition' . $teamNid] = [
        '#type' => 'textfield',
        '#config_target' => $settingsPrefix . 'competition',
        '#title' => 'Competition ID',
        '#description' => $settingsPrefix . 'competition',
      ];
      $form[$index]['division' . $teamNid] = [
        '#type' => 'textfield',
        '#config_target' => $settingsPrefix . 'division',
        '#title' => 'Division ID',
        '#description' => $settingsPrefix . 'division',
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  protected function getSectionSortName($entity, $fieldName = "field_section")
  {
    $data = [];
    if ($field = $entity->get($fieldName)) {
      if (!$field->isEmpty()) {
        foreach ($field->referencedEntities() as $term) {
          $data[] = $term->label();
        }
      }
    }
    $data[] = $entity->getTitle();
    return implode(":", $data);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames()
  {
    return [self::SETTINGS_NAME];
  }
}
