<?php

namespace Drupal\nrfc_fixtures\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\node\Entity\Node;
use Drupal\nrfc\Service\NRFC;
use Drupal\nrfc_fixtures\Entity\NRFCFixtures;
use Drupal\nrfc_fixtures\Entity\NRFCFixturesRepo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Defines the admin section controller.
 */
final class FixturesController extends ControllerBase {

  private LoggerChannel $logger;

  private NRFC $nrfc;

  private NRFCFixturesRepo $nrfcFixturesRepo;

  public function __construct(
    LoggerChannel $logger,
    NRFC $nrfc,
    NRFCFixturesRepo $nrfcFixturesRepo
  ) {
    $this->logger = $logger;
    $this->nrfc = $nrfc;
    $this->nrfcFixturesRepo = $nrfcFixturesRepo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(\Symfony\Component\DependencyInjection\ContainerInterface $container) {
    return new static(
      $container->get('logger.channel.nrfc'),
      $container->get('nrfc.nrfc'),
      $container->get('nrfc_fixtures.repo'),
    );
  }

  public function sectionsTitle(string $sections, Request $request): string {
    return ucwords(str_replace('_', ' ', $sections)) . " Fixtures";
  }

  public function sections(string $sections, Request $request): NotFoundHttpException|array {
    // TODO This should be doable in the route
    $allowedSections = [
      "senior",
      "youth",
      "youth-and-minis",
      "minis",
      "boys",
      "girls",
    ];
    $_sections = explode("-and-", $sections);
    $fixtures = [];
    foreach ($_sections as $term) {
      $fixtures = array_merge($fixtures, $this->nrfcFixturesRepo->getFixturesByTermName($term));
    }

    $ordered = $this->nrfc->getTeamsInOrder();
    $headers = [];
    $rows = [];
    foreach ($fixtures as $teamName => $fs) {
      // The headers are populated with each row of data
      $headers[] = $teamName;
      foreach ($fs as $f) {
        $date = $f->date->value;
        if (!in_array($date, array_keys($rows))) {
          $rows[$date] = [];
        }
        // If there are two fixtures on the same date for a team, only the later one will appear
        $rows[$date][$teamName] = $f;
      }
    }
    // TODO - Sort $teams
    return [
      '#theme' => 'nrfc_fixtures_multiple',
      '#headers' => $headers,
      '#fixtures' => $rows,
      '#attached' => [
        'library' => [
          'nrfc_fixtures/nrfc_fixtures',
        ],
        'drupalSettings' => [
          'nrfc_fixtures' => "some data",
        ],
      ],
    ];
  }

  public function detailTitle(NRFCFixtures $fixture, Request $request): string {
    $team = Node::load($fixture->team_nid->value)->getTitle();
    $opponent = $fixture->opponent->value;
    $ha = $fixture->home->value;
    if ($ha == "Away") {
      $home = $opponent;
      $away = $team;
    } else {
      $home = $team;
      $away = $opponent;
    }
    // FIXME - Use drupal time formats
    $date = date("d/m/y", strtotime($fixture->date->value));
    return sprintf(
      "%s vs %s, %s",
      $home, $away, $date
    );
  }

  public function detail(NRFCFixtures $fixture, Request $request) {
    $team = Node::load($fixture->team_nid->value)->getTitle();
    $date = date("D jS M, Y", strtotime($fixture->date->value));

    return [
      '#theme' => 'nrfc-fixtures-detail',
      '#team' => $team,
      '#date' => $date,
      '#fixture' => $this->nrfcFixturesRepo->fixtureToArray($fixture),
    ];
  }

  public function teamTitle(Node $team, Request $request): string {
    return $team->getTitle() . " Fixtures";
  }

  public function team(Node $team, Request $request): array {
    return [
      '#theme' => 'nrfc_fixtures_team',
      '#fixtures' => $this->nrfcFixturesRepo->getTeam($team),
      '#attached' => [
        'library' => [
          'nrfc_fixtures/nrfc_fixtures',
        ],
        'drupalSettings' => [
          'nrfc_fixtures' => "some data",
        ],
      ],
    ];
  }

}
