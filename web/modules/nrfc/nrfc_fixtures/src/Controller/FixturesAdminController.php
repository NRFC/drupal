<?php

namespace Drupal\nrfc_fixtures\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the admin section controller.
 */
class FixturesAdminController extends ControllerBase
{
  /**
   * Returns a render-able array for the admin page.
   */
  public function adminPage()
  {
    try {
      $query = $this->entityTypeManager()
        ->getStorage('node')
        ->getQuery();
    } catch (InvalidPluginDefinitionException|PluginNotFoundException $e) {
      $this->getLogger(__CLASS__)->error($e->getMessage());
      return [
        "#markup" => "Error generating accessing the DB, something is really fubar'd"
      ];
    }

    $nids = $query
      ->condition('type', 'team')
      ->accessCheck(TRUE)
      ->execute();
    $nodes = \Drupal\node\Entity\Node::loadMultiple($nids);

    return [
      '#theme' => 'nrfc_fixtures_index',
      '#teams' => $nodes,
    ];
  }

  public function teamPage(Node $team, Request $request)
  {
    try {
      $query = $this->entityTypeManager()
        ->getStorage('nrfc_fixtures')
        ->getQuery();
    } catch (InvalidPluginDefinitionException|PluginNotFoundException $e) {
      $this->getLogger(__CLASS__)->error($e->getMessage());
      return [
        "#markup" => "Error generating accessing the DB, something is really fubar'd"
      ];
    }

//    $rows = $query
//      ->condition('team_nid', $team->id())
//      ->accessCheck(TRUE)
//      ->execute();

    $rows = [
      ["one", "Home"],
      ["two", "Away"],
    ];

    $build = [
      '#theme' => 'nrfc_fixtures_team',
      '#team' => $team,
      '#rows' => "{}",
      '#attached' => [
        'library' => [
          'nrfc_fixtures/nrfc_fixtures',
        ],
        'drupalSettings' => [
          'nrfc_fixtures' => [
            "rows" => $rows
          ]
        ]
      ],
    ];

    return $build;
  }
}
