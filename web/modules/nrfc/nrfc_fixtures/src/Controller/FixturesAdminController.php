<?php

namespace Drupal\nrfc_fixtures\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\node\Entity\Node;
use Drupal\nrfc_fixtures\Entity\NRFCFixtures;
use Drupal\nrfc_fixtures\Entity\NRFCFixturesRepo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Defines the admin section controller.
 */
final class FixturesAdminController extends ControllerBase
{
  private LoggerChannel $logger;
  private NRFCFixturesRepo $nrfcFixturesRepo;

  public function __construct(
    LoggerChannel    $logger,
    NRFCFixturesRepo $nrfcFixturesRepo)
  {
    $this->logger = $logger;
    $this->nrfcFixturesRepo = $nrfcFixturesRepo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(\Symfony\Component\DependencyInjection\ContainerInterface $container)
  {
    return new static(
      $container->get('logger.channel_nrfc'),
      $container->get('nrfc_fixtures.repo'),
    );
  }

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

  public function templateDownload(Request $request): StreamedResponse
  {
    $response = new StreamedResponse();
    $response->setCallback(function () {
      $handle = fopen('php://output', 'w+');

      $data = [$this->getHeader()];
      $data[] = ["yyyy/mm/dd", "hh:mm", "H or A", "League", "Free text"];
      $data[] = ["", "", "", "", ""];
      $data[] = ["Delete this all following rows ", "", "", "", ""];
      $data[] = ["h/a should be either 'H' for a home game, or 'A' for an away game:", "", "", "", ""];
      $data[] = ["Type should be one of these:", "", "", "", ""];
      $data[] = ["", "L", "for League", "", ""];
      $data[] = ["", "F", "for Friendly", "", ""];
      $data[] = ["", "Fe", "for Festival", "", ""];
      $data[] = ["", "T", "for Tournament", "", ""];
      $data[] = ["", "NC", "for National Cup", "", ""];
      $data[] = ["", "CC", "for County Cup", "", ""];
      $data[] = ["", "C", "for Cup (Other)", "", ""];

      // Stream each row.
      foreach ($data as $row) {
        fputcsv($handle, $row);
      }

      fclose($handle);
    });

    // Set CSV file name and content type.
    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="fixtures_template.csv"');

    return $response;
  }

  private function getHeader(): array
  {
    return ["date", "ko", "h/a", "type", "opponent"];
  }

  public function teamDownload(Node $team, Request $request)
  {
    $rows = $this->nrfcFixturesRepo->getFixturesForTeamAsArray($team);
    $response = new StreamedResponse();
    $response->setCallback(function () use ($rows) {
      $handle = fopen('php://output', 'w+');

      fputcsv($handle, $this->getHeader());
      // Stream each row.
      foreach ($rows as $row) {
        fputcsv($handle, [
          $row["date"],
          $row["ko"],
          $row["home"] == "Home" ? "H" : "A",
          $row["match_type"],
          $row["opponent"],
          $row["result"],
          $row["report"],
          $row["referee"],
          $row["food"],
          $row["food_notes"],
        ]);
      }

      fclose($handle);
    });

    // Set CSV file name and content type.
    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set(
      'Content-Disposition',
      sprintf(
        'attachment; filename="%s.csv"',
        strtolower(preg_replace('/[^a-zA-Z0-9-]+/', '-', $team->getTitle())))
    );

    return $response;
  }

  public function fixtureUpdate(Node $team, Request $request)
  {
    $data = $request->getPayload();
    $errors = [];
    foreach ($data as $row) {
      if (is_array($row)) {
        if (!$this->nrfcFixturesRepo->createOrUpdateFixture($row, $team)) {
          $errors[] = "Error setting fixture data " . implode(", ", $row);
        }
      } else {
        $errors[] = "Fixture data passed was not an array, " . $row;
      }
    }

    if (count($errors)) {
      $this->logger->warning(implode("|", $errors));
    }

    return new Response($status = 204);
  }
}