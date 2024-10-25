<?php /** @noinspection ALL */

namespace Drupal\Tests\nrfc_fixtures\Unit;

use Drupal;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\UnitTestCase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\nrfc\NRFC;
use Drupal\nrfc_fixtures\Entity\NRFCFixturesRepo;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

class NRFCFixturesRepoTest extends UnitTestCase {

  protected static $modules = [
    'nrfc_fixtures',
  ];

  public function testMakeReportName() {
    $team = new MockTeam();
    $repo = new MockRepo();
    $name = $repo->makeReportName($team);
    $this->assertEquals('06/03/22 - Team Nostromo [3145]', $name);
  }

  public function testParseType() {
    $repo = new MockRepo();
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
    $repo = new MockRepo();
    $this->assertEquals(
      3145,
      $repo->getReportId("12/12/12 - Team Toby [3145]")
    );
    $this->assertEquals(
      0,
      $repo->getReportId("12/12/12 - Team Toby [abc]")
    );
    $this->assertEquals(
      0,
      $repo->getReportId("NoNumbers")
    );
    // FIXME - probably wrong behaviour in the repo class
    $this->assertEquals(
      121212,
      $repo->getReportId("12/12/12-NoSpaces")
    );
  }

  protected function setUp(): void {
    parent::setUp();
  }

}

/**
 * Overide the constructor for unit tests. This way we can test the functions
 * that don't use services
 */
class MockRepo extends NRFCFixturesRepo {
  public function __construct() {}
}

/**
 * Mock team that supplies only the functions needed for functions that don't
 * need services
 */
class MockTeam extends Node {
  public function __construct() {
    // Leave empty
  }

  public function getTitle() {
    return "Team Nostromo";
  }
  public function getChangedTime() {
    return strtotime("03/06/2122");
  }
  public function id() {
    return 3145;
  }
}
