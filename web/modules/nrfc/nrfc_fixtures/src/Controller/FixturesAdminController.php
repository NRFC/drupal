<?php

namespace Drupal\nrfc_fixtures\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
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

    $results = $query
      ->condition('team_nid', $team->id())
      ->accessCheck(TRUE)
      ->execute();

    $rows = [];
    foreach ($results as $result) {
      $rows[] = [
        $result->get("date"),
        $result->get("ko"),
        $result->get("home"),
        $result->get("type"),
        $result->get("opponent"),
        $result->get("result"),
        $result->get("report"),
        $result->get("referee"),
        $result->get("food"),
        $result->get("food_notes"),
      ];
    }
    $rows[] = ["", "", "", "", "", "", "", "", "", "",];

    $build = [
      '#theme' => 'nrfc_fixtures_team',
      '#team' => $team->getTitle(),
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

    $form = $this->formBuilder()->getForm(
      'Drupal\nrfc_fixtures\Form\NrfcFixturesUploadForm'
    );
    $build['#upload_form'] = $form;

    return $build;
  }
}
