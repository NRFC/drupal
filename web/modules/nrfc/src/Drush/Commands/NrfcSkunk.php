<?php

namespace Drupal\nrfc\Drush\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal;
use Drupal\Core\Utility\Token;
use Drupal\node\Entity\Node;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class NrfcSkunk extends DrushCommands {

  private Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager;

  /**
   * Command description here.
   */
  #[CLI\Command(name: 'nrfc:skunk')]
  #[CLI\Usage(name: 'nrfc:skunk', description: 'Usage description')]
  public function commandName() {
    $this->entityTypeManager = Drupal::entityTypeManager();
    $teams = Node::loadMultiple(
      $this->entityTypeManager
        ->getStorage("node")
        ->getQuery()
        ->accessCheck()
        ->condition('status', 1)
        ->condition('type', 'team')
        ->execute()
    );
  }
}
