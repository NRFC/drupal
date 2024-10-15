<?php /** @noinspection ALL */

namespace Drupal\nrfc\Unit;

use Drupal;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\nrfc_fixtures\Entity\NRFCFixtures;
use Drupal\nrfc_fixtures\Entity\NRFCFixturesRepo;
use Drupal\Tests\UnitTestCase;

class NRFCFixturesRepoTest extends UnitTestCase {

  protected NRFCFixturesRepo $repo;

  public function testMakeReportName() {
    $n = Node::create([
      "type" => "team",
      "title" => "Team Toby",
    ]);
    $n->save();
    $repo = Drupal::service('nrfc_fixtures.repo');
    $name = $repo->makeReportName($n);
    $this->assertEquals(
      sprintf(
        "%s - Team America [3145]",
        date("d/m/y")
      ),
      $name
    );
  }

  public function testParseType() {
    $repo = Drupal::service('nrfc_fixtures.repo');
    // Test matches
    $this->assertEquals("League", $repo->parseType("L"));
    $this->assertEquals("Friendly", $repo->parseType("F"));
    $this->assertEquals("Festival", $repo->parseType("FE"));
    $this->assertEquals("Tournament", $repo->parseType("T"));
    $this->assertEquals("National Cup", $repo->parseType("NC"));
    $this->assertEquals("County Cup", $repo->parseType("CC"));
    $this->assertEquals("Cup", $repo->parseType("C"));
    $this->assertEquals("", $repo->parseType("Some random text"));
    $this->assertEquals("", $repo->parseType(""));

    // Test case wobbles
    $this->assertEquals("League", $repo->parseType("l"));
  }

  public function testGetReportId() {
    $repo = Drupal::service('nrfc_fixtures.repo');
    $this->assertEquals(
      3145,
      $repo->getReportId("12/12/12 - Team Toby [3145]")
    );
    $this->assertEquals(
      0,
      $repo->getReportId("12/12/12 - Team Toby [abc]")
    );
    // FIXME - probably wrong behaviour in the repo class
    $this->assertEquals(
      121212,
      $repo->getReportId("12/12/12-NoSpaces")
    );
  }

  public function testCaseInsensitiveTeamSectionSearch() {
    $repo = Drupal::service('nrfc_fixtures.repo');

    $this->assertNull($repo->caseInsensitiveTeamSectionSearch("Not found"));
    $this->assertEquals(
      new MockTerm(1),
      $repo->caseInsensitiveTeamSectionSearch("term 1")
    );
  }

  public function testFixturesToArray() {
    $repo = Drupal::service('nrfc_fixtures.repo');

    $fix = new NRFCFixtures([
      "team_nid" => 123,
    ], "nrfc_fixtures");


    $data = $repo->fixturesToArray($fix);
  }

  //  public function testCreateOrUpdateFixture() {}
  //
  //  public function testDeleteAll() {}
  //
  //  public function testGetFixturesForTeam() {}
  //
  //  public function testGetFixturesForTeamAsArray() {}
  //
  //  public function testFixtureToArray() {}
  //
  //  public function testGetFixturesByTermName() {}

  protected function setUp(): void {
    parent::setUp();
    $container = new ContainerBuilder();
    \Drupal::setContainer($container);

    // Logger
    $logger = $this->getMockBuilder(Drupal\Core\Logger\LoggerChannel::class)
      ->disableOriginalConstructor()
      ->getMock();

    // nrfc fixtures storage
    $nrfcFixturesEntityStorage = $this->getMockBuilder(EntityStorageInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $nrfcFixturesEntityStorage->expects($this->any())
      ->method('loadmultiple')
      ->willReturn([]); // getFixturesForTeam
    $nrfcFixturesEntityStorage->expects($this->any())
      ->method('load')
      ->willReturn("foo"); // single fixture
    $nrfcFixturesEntityStorage->expects($this->any())
      ->method('create')
      ->willReturn("foo");
    $nrfcFixturesEntityStorage->expects($this->any())
      ->method('loadByProperties')
      ->willReturn([]);

    /************************************************************** /
     * Entity field manager
     * /**************************************************************/
    $entity_field_manager = $this->getMockBuilder(EntityFieldManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $container->set('entity_field.manager', $entity_field_manager);
    $entity_field_manager->expects($this->any())
      ->method('getFieldDefinitions')
      ->willReturn([]);

    /************************************************************** /
     * TAXONOMY TERMS
     * /**************************************************************/
    $terms = [
      new MockTerm(1),
      new MockTerm(2),
      new MockTerm(3),
      new MockTerm(4),
    ];
    $termsEntityStorage = new MockTermStorage($terms);
    /************************************************************** /
     * SET UP ENTITY MANAGERS FOR CREATING NODES
     * /**************************************************************/
    // Create a mock node to be saved
    $node = $this->getMockBuilder(Node::class)
      ->disableOriginalConstructor()
      ->getMock();
    $node->expects($this->any())
      ->method('getChangedTime')
      ->willReturn(strtotime("now"));
    $node->expects($this->any())
      ->method('getTitle')
      ->willReturn("Team America");
    $node->expects($this->any())->method('id')->willReturn(3145);

    // Create the storage mock, this will have the ->create function called on it
    $entity_storage = $this->getMockBuilder(Drupal\Core\Entity\EntityStorageInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    // MAYBE SWITCH HERE ON TYPE AND TITLE PASSED
    $entity_storage->expects($this->any())
      ->method('create')
      ->willReturn($node);
    $entity_storage->expects($this->any())
      ->method('load')
      ->willReturn(new MockTerm(1));

    // Storage class, just needs to exist
    $entity_type_repository = $this->getMockBuilder(Drupal\Core\Entity\EntityTypeRepositoryInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $container->set('entity_type.repository', $entity_type_repository);
    // Create the repo, this will be returned when the base node storage is loaded

    $entity_type_manager = $this->getMockBuilder(EntityTypeManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $container->set('entity_type.manager', $entity_type_manager);
    // Null for nodes
    $entity_type_manager->expects($this->any())
      ->method('getstorage')
      ->willReturnCallback(function(string|null $property) use ($entity_storage, $nrfcFixturesEntityStorage, $termsEntityStorage) {
        switch ($property) {
          case "nrfc_fixtures":
            return $nrfcFixturesEntityStorage;
          case "taxonomy_term":
            return $termsEntityStorage;
          default: // null
            return $entity_storage;
        }
      });
    $entity_type_manager->expects($this->any())
      ->method('getDefinition')
      ->willReturnCallback(function(string|null $property) use ($entity_storage, $entity_type_manager, $nrfcFixturesEntityStorage, $termsEntityStorage) {
        switch ($property) {
          case "nrfc_fixtures":
            return new MockEntityTypemanager();
          default: // null
            return $entity_storage;
        }
      });
    /** FINISH NODE LOAD */

    $container->set('nrfc_fixtures.repo', new NRFCFixturesRepo(
      $entity_type_manager,
      $logger,
    ));
  }

}

class MockTerm {

  public function __construct($tid) {
    $this->name = "Term " . $tid;
    $this->tid = $tid;
  }

  public static function load($id) {
    return new MockTerm(1);
  }
}

class MockTermStorage {
  public function __construct($terms) {
    $this->terms = $terms;
  }

  public function loadTree($vid) {
    return $this->terms;
  }
}

class MockEntityTypemanager {
  public function getKey() {
    return "DDDDD";
  }

  public function getKeys() {
    return ["DDDDD"];
  }
}
