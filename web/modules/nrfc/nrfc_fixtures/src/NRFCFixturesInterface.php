<?php

declare(strict_types=1);

namespace Drupal\nrfc_fixtures;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a nrfc fixtures entity type.
 */
interface NRFCFixturesInterface extends ContentEntityInterface, EntityChangedInterface {

}
