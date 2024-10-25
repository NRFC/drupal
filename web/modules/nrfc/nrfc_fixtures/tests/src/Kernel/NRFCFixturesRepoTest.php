<?php /** @noinspection ALL */

namespace Drupal\Tests\nrfc_fixtures\Kernel;

use Drupal;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\nrfc\NRFC;
use Drupal\nrfc_fixtures\Entity\NRFCFixturesRepo;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

class NRFCFixturesRepoTest extends KernelTestBase {

  use NodeCreationTrait, ContentTypeCreationTrait, UserCreationTrait;

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

  protected NRFCFixturesRepo $repo;

  protected ?Term $term = NULL;

  protected array $terms = [];

  protected array $teams = [];

  public function testMakeReportName() {
    $team = $this->teams[0];
    $repo = Drupal::service('nrfc_fixtures.repo');
    $name = $repo->makeReportName($team);
    $this->assertEquals(
      sprintf(
        "%s - Team America [%d]",
        date("d/m/y"),
        $team->id()
      ),
      $name
    );
  }

  public function testParseType() {
    // Test matches
    $this->assertEquals("League", $this->repo->parseType("L"));
    $this->assertEquals("Friendly", $this->repo->parseType("F"));
    $this->assertEquals("Festival", $this->repo->parseType("FE"));
    $this->assertEquals("Tournament", $this->repo->parseType("T"));
    $this->assertEquals("National Cup", $this->repo->parseType("NC"));
    $this->assertEquals("County Cup", $this->repo->parseType("CC"));
    $this->assertEquals("Cup", $this->repo->parseType("C"));
    $this->assertEquals("", $this->repo->parseType("Some random text"));
    $this->assertEquals("", $this->repo->parseType(""));

    // Test case wobbles
    $this->assertEquals("League", $this->repo->parseType("l"));
  }

  public function testGetReportId() {
    $this->assertEquals(
      3145,
      $this->repo->getReportId("12/12/12 - Team Toby [3145]")
    );
    $this->assertEquals(
      0,
      $this->repo->getReportId("12/12/12 - Team Toby [abc]")
    );
    $this->assertEquals(
      0,
      $this->repo->getReportId("NoNumbers")
    );
    // FIXME - probably wrong behaviour in the repo class
    $this->assertEquals(
      121212,
      $this->repo->getReportId("12/12/12-NoSpaces")
    );
  }

  public function testCaseInsensitiveTeamSectionSearch() {
    $this->assertNull($this->repo->caseInsensitiveTeamSectionSearch("Not found"));
    $term = $this->repo->caseInsensitiveTeamSectionSearch("lEAF 2/1");
    $this->assertTrue($term instanceof Term);
    // term equals seem VERY costly, we'll compare name and id
    $this->assertTrue(
      $this->terms[4]->id() == $term->id() &&
      $this->terms[4]->name->value == $term->name->value
    );
  }

  public function testDeleteAll() {
    $team = $this->teams[0];

    $count = \Drupal::entityTypeManager()
      ->getStorage('nrfc_fixtures')
      ->getQuery()
      ->accessCheck()
      ->count()
      ->execute();
    $this->assertEquals($count, 45);

    $this->repo->deleteAll($team);

    $count = \Drupal::entityTypeManager()
      ->getStorage('nrfc_fixtures')
      ->getQuery()
      ->accessCheck()
      ->count()
      ->execute();
    // Each team has 15 fixtures
    $this->assertEquals($count, 30);
  }

  public function testCreateOrUpdateFixture() {
    // Create
    $fixtureData = [
      "date" => "03/06/2122",
      "ko" => "14:30",
      "home" => "a",
      "match_type" => "Fe",
      "opponent" => "Xenomporph",
      "result" => "0:99",
      "report" => sprintf("title doesn't matter [%d]", $this->report->id()),
      "referee" => "Ash Hyperdyne",
      "food" => "7",
      "food_notes" => "No Veggie, Some allergies",
    ];
    $fixture = $this->repo->createOrUpdateFixture($fixtureData, $this->teams[0]);
    $this->assertInstanceOf(Drupal\nrfc_fixtures\Entity\NRFCFixtures::class, $fixture);
    $this->assertEquals($fixtureData["date"], $fixture->date->value);
    $this->assertEquals($fixtureData["ko"], $fixture->ko->value);
    $this->assertEquals("Away", $fixture->home->value);
    $this->assertEquals("Festival", $fixture->match_type->value);
    $this->assertEquals($fixtureData["opponent"], $fixture->opponent->value);
    $this->assertEquals($fixtureData["result"], $fixture->result->value);
    $this->assertEquals($this->report->id(), $fixture->report[0]->target_id);
    $this->assertEquals($fixtureData["referee"], $fixture->referee->value);
    $this->assertEquals($fixtureData["food"], $fixture->food->value);
    $this->assertEquals($fixtureData["food_notes"], $fixture->food_notes->value);

    // Update
    $fixtureData["nid"] = $fixture->id();
    $fixtureData["date"] = "99/99/99";
    $fixtureData["home"] = "H";
    $fixtureData["match_type"] = "NC";
    $fixture = $this->repo->createOrUpdateFixture($fixtureData, $this->teams[0]);
    $this->assertInstanceOf(Drupal\nrfc_fixtures\Entity\NRFCFixtures::class, $fixture);
    $this->assertEquals($fixtureData["date"], $fixture->date->value);
    $this->assertEquals("Home", $fixture->home->value);
    $this->assertEquals("National Cup", $fixture->match_type->value);

    /*
     * Cheeky, we'll check the fixtures to string method here to avoid a whole
     * test class and mothod just to check one method.
     */
    $this->assertEquals(
      'Drupal\nrfc_fixtures\Entity\NRFCFixtures nid=46 team_id=1 date=99/99/99 ko=14:30 ha=Home match_type=National Cup opponent=Xenomporph result=0:99 report_id=4 referee=Ash Hyperdyne food=7 food_notes=No Veggie, Some allergies',
      $fixture->__toString()
    );
  }

  public function testGetFixturesForTeam() {
    $fixtures = $this->repo->getFixturesForTeam($this->teams[0]);
    $this->assertCount(15, $fixtures);
    $fixtures = $this->repo->getFixturesForTeam($this->teams[0]->id());
    $this->assertCount(15, $fixtures);
  }

  // FIXME - Add a specific test for this, it gets caught by the other methods
  // public function testFixtureToArray() {}

  public function testGetFixturesByTermName() {
    $fixtures = $this->repo->getFixturesByTermName($this->term);
    $this->assertCount(2, $fixtures);
    $fixtures = $this->repo->getFixturesByTermName($this->term->getName());
    $this->assertCount(2, $fixtures);
  }

  protected function setUp(): void {
    parent::setUp();

    // Install *module* schema for node/user modules.
    $this->installSchema('node', ['node_access']);
    $this->installSchema('user', ['users_data']);
    //    $this->installSchema('options');

    // Install *entity* schema for the node entity.
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('nrfc_fixtures');

    // Install any config provided by the enabled.
    $this->installConfig(['field', 'node', 'text', 'filter', 'user']);

    // Create an owner account.
    $this->owner = $this->createUser([], 'test_user');  // Create a field.

    // Create vocab
    $vocabulary = Vocabulary::create([
      'vid' => Drupal\nrfc\Service\NRFC::TEAM_SECTION_ID,
      'name' => "Team Section",
    ]);
    $vocabulary->save();

    // Create terms
    $this->terms = [];
    for ($i = 1; $i < 3; $i++) {
      $t = Term::create([
        'name' => 'Branch ' . $i,
        'vid' => Drupal\nrfc\Service\NRFC::TEAM_SECTION_ID,
      ]);
      $t->save();
      $this->terms[] = $t;
      for ($j = 1; $j < 3; $j++) {
        $t = Term::create([
          'name' => 'Leaf ' . $i . "/" . $j,
          'parent' => $t->id(),
          'vid' => Drupal\nrfc\Service\NRFC::TEAM_SECTION_ID,
        ]);
        $t->save();
        if (!$this->term) {
          $this->term = $t;
        }
        $this->terms[] = $t;
      }
    }

    // Create team node type & fields
    $handler_settings = [
      'target_bundles' => [
        $vocabulary->id() => $vocabulary->id(),
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

    // Create 3 teams
    $team = Node::create([
      "type" => "team",
      "title" => "Team America",
      'body' => [
        'value' => 'This is the body of my programmatically created node.',
        'format' => 'full_html',
      ],
    ]);
    $team->field_section = ["target_id" => 99];
    $team->save();
    $this->teams[] = $team;

    $team = Node::create([
      "type" => "team",
      "title" => "Team Aliens",
      'body' => [
        'value' => 'This is the body of my programmatically created node.',
        'format' => 'full_html',
      ],
    ]);
    $team->field_section = ["target_id" => $this->term->id()];
    $team->save();
    $this->teams[] = $team;

    $team = Node::create([
      "type" => "team",
      "title" => "Team Nostromo",
      'body' => [
        'value' => 'This is the body of my programmatically created node.',
        'format' => 'full_html',
      ],
    ]);
    $team->field_section = ["target_id" => $this->term->id()];
    $team->save();
    $this->teams[] = $team;

    // Create fixtures
    for ($i = 0; $i < 45; $i++) {
      Drupal\nrfc_fixtures\Entity\NRFCFixtures::create([
        "title" => "Fixture " . $i,
        "team_nid" => $this->teams[$i % 3]->id(),
      ])->save();
    }

    // Create a report
    $this->report = Node::create([
      "type" => "match_report",
      "title" => "Game on LV-426",
      "field_team" => $team->id(),
    ]);
    $this->report->save();

    // Finally create the repo we are testing
    $this->repo = Drupal::service('nrfc_fixtures.repo');
  }

}
