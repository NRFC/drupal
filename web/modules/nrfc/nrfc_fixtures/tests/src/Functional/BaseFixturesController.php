<?php

namespace Drupal\Tests\nrfc_fixtures\Functional;

use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\nrfc\Service\NRFC;
use Drupal\nrfc_fixtures\Entity\NRFCFixtures;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;

abstract class BaseFixturesController extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'user',
    'system',
    'field',
    'datetime',
    'node',
    'text',
    'options',
    'taxonomy',
    'filter',
    'nrfc',
    'nrfc_fixtures',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'nrfc_barrio';

  /**
   * The installation profile to use with this test.
   *
   * We need the 'minimal' profile in order to make sure the Tool block is
   * available.
   *
   * @var string
   */
  protected $profile = 'minimal';
  protected function setUp(): void {
    parent::setUp();

    $this->vocabulary = Vocabulary::create([
      'vid' => NRFC::TEAM_SECTION_ID,
      'name' => "Team Section",
    ]);
    $this->vocabulary->save();

    $this->terms = [];

    $this->terms['youth'] = Term::create([
      'name' => 'Youth',
      'vid' => $this->vocabulary->id(),
    ]);
    $this->terms['youth']->save();

    $this->terms['Boys'] = Term::create([
      'name' => 'Boys',
      'vid' => $this->vocabulary->id(),
      'parent' => $this->term_youth,
    ]);
    $this->terms['Boys']->save();

    $this->terms['Girls'] = Term::create([
      'name' => 'Girls',
      'vid' => $this->vocabulary->id(),
      'parent' => $this->term_youth,
    ]);
    $this->terms['Girls']->save();

    $handler_settings = [
      'target_bundles' => [
        $this->vocabulary->id() => $this->vocabulary->id(),
      ],
      'auto_create' => TRUE,
    ];

    NodeType::create([
      'type' => 'team',
      'name' => 'Team',
      'display_submitted' => FALSE,
    ])->save();
    // Add the term field.
    FieldStorageConfig::create([
      'field_name' => 'field_section',
      'type' => 'entity_reference',
      'entity_type' => 'node',
      'cardinality' => 1,
      'settings' => [
        'target_type' => 'taxonomy_term',
      ],
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_section',
      'entity_type' => 'node',
      'bundle' => 'team',
      'label' => 'Terms',
      'settings' => [
        'handler' => 'default',
        'handler_settings' => $handler_settings,
      ],
    ])->save();

    for ($i = 0; $i < 5; $i++) {
      $team = Node::create([
        "type" => "team",
        "title" => "Team " . $i,
      ]);
      // should be indexes 1,2,1,2,1, so 3 x boys, 2 x girls
      $team->field_section = [
        "target_id" => array_values($this->terms)[($i + 1) % count($this->terms)]->id(),
      ];
      $team->save();
//      $this->teams[] = $team;

      // Create fixtures
      for ($i = 0; $i < 15; $i++) {
        NRFCFixtures::create([
          "title" => "Fixture " . $i,
          "team_nid" => $team->id(),
        ])->save();
      }
    }
  }
}
