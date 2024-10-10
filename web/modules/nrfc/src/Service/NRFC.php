<?php

namespace Drupal\nrfc\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

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

  public function getTeamsInOrder() {
    /* @var Term[] $terms */
    $terms = $this->etmi
      ->getStorage("taxonomy_term")
      ->loadTree(
        NRFC::TEAM_SECTION_ID,
        0,
        NULL,
        TRUE
      );
    $teams = [];
    foreach ($terms as $term) {
      $nodes = Node::loadMultiple(
        $this->etmi
          ->getStorage("node")
          ->getQuery()
          ->condition('field_section', $term->id())
          ->sort('title', 'ASC')
          ->accessCheck()
          ->execute()
      );
      $teams = array_merge(
        $teams,
        array_map(function($node){
          return $node->getTitle();
        },$nodes));
    }

    return $teams;
  }

}
