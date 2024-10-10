<?php

namespace Drupal\nrfc\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\taxonomy\Entity\Vocabulary;

  use Drupal\taxonomy\Entity\Term;
  use Drupal\taxonomy\TermStorageInterface;

final class NRFC {

  const TEAM_SECTION_ID = "team-section";

  private EntityTypeManagerInterface $etmi;

  private LoggerChannel $l;

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannel $logger,
  ) {
    $this->etmi = $entity_type_manager;
    $this->l = $logger;
  }
}
