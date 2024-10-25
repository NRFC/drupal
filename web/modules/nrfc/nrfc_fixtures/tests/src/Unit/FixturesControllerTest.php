<?php /** @noinspection ALL */

namespace Drupal\nrfc_fixtures\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\nrfc_fixtures\Entity\NRFCFixtures;
use Drupal\Tests\UnitTestCase;

class FixturesControllerTest extends UnitTestCase {

  protected static $modules = [
    'nrfc_fixtures',
    'nrfc',
  ];

  public function testSectionsTitle() {

  }

  public function testTeamTitle() {}

  public function testToString() {}

  protected function setUp(): void {
    parent::setUp();
    $this->fix = new MockFixtures();
  }

}

class MockFixtures extends NRFCFixtures {
  public function __construct() {}
}
