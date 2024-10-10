<?php

namespace Drupal\nrfc_fixtures\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\node\Entity\Node;
use Drupal\nrfc\Service\NRFC;
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
      $container->get('logger.channel_nrfc'),
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

    // Sort $teams
    /*
    {
      "header: { "Minis", "U13B", U14B", U15B" }
      "dates": {
        "01/01/01": { fixture1, fixture2, fixture3, fixture4, },
        "01/01/01": { fixture1, fixture2, fixture3, fixture4, },
        "01/01/01": { fixture1, fixture2, fixture3, fixture4, },
      }
    }
     */
    return [
      '#theme' => 'nrfc_fixtures_multiple',
      '#fixtures' => $fixtures,
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
