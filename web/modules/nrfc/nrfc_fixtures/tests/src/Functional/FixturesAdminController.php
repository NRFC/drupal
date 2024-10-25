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

class FixturesAdminController extends BaseFixturesController {

  public function testAdminPage() {
    $this->drupalLogin($this->createUser(['access content']));
    $assert = $this->assertSession();

    $path = Url::fromRoute('nrfc_fixtures.admin_page');
    $this->drupalGet($path);
    $assert->statusCodeEquals(200);

  }
}
