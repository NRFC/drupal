<?php

declare(strict_types=1);

namespace Drupal\nrfc_league_table_scraper\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\Annotation\FieldType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'nrfc_league_table' field type.
 *
 * @FieldType(
 *   id = "nrfc_league_table",
 *   label = @Translation("League Table"),
 *   description = @Translation("Some description."),
 *   default_widget = "nrfc_league_table",
 *   default_formatter = "nrfc_league_table_default",
 * )
 */
final class LeagueTableScraperItem extends FieldItemBase {
  private string $xpath;
  private string $url;

  /**
   * {@inheritdoc}
   */
  public function isEmpty(): bool {
    return $this->url === NULL && $this->xpath === NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {

    $properties['url'] = DataDefinition::create('uri')
      ->setLabel(t('URL'));
    $properties['xpath'] = DataDefinition::create('string')
      ->setLabel(t('XPath'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints(): array {
    $constraints = parent::getConstraints();
    $options['url']['NotBlank'] = [];
    $options['xpath']['NotBlank'] = [];

    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints[] = $constraint_manager->create('ComplexData', $options);

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {

    $columns = [
      'url' => [
        'type' => 'varchar',
        'length' => 2048,
      ],
      'xpath' => [
        'type' => 'varchar',
        'length' => 255,
      ],
    ];

    $schema = [
      'columns' => $columns,
    ];

    return $schema;
  }
}
