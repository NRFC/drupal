<?php

declare(strict_types=1);

namespace Drupal\nrfc_fixtures\Entity;

use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\node\Entity\Node;


class NRFCFixturesRepo extends EntityRepository
{
  private LoggerChannel $logger;

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface   $language_manager,
    ContextRepositoryInterface $context_repository,
    LoggerChannel              $logger,

  )
  {
    parent::__construct($entity_type_manager, $language_manager, $context_repository);
    $this->logger = $logger;
  }

  public static function makeReportName(Node|int $report): ?string
  {
    if (!$report instanceof Node) {
      $report = Node::load(intval($report));
    }
    if (!$report) {
      return null;
    }
    return sprintf(
      "%s - %s [%d]",
      date("d/m/y", $report->getChangedTime()),
      $report->getTitle(),
      $report->id(),
    );
  }

  public function getFixturesForTeamAsArray(Node $team): array
  {
    return $this->fixturesToArray($this->getFixturesForTeam(($team)));
  }

  public function fixturesToArray(array $fixtures): array
  {
    $data = [];
    foreach ($fixtures as $fixture) {
      $data[] = $this->fixtureToArray($fixture);
    }
    return $data;
  }

  public function fixtureToArray(NRFCFixtures $fixture): array
  {
    return [
      'nid' => $fixture->id(),
      'team_nid' => $fixture->team_nid,
      'date' => $fixture->date->value,
      'ko' => $fixture->ko->value,
      'home' => $fixture->home->value,
      'match_type' => $fixture->match_type->value,
      'opponent' => $fixture->opponent->value,
      'result' => $fixture->result->value,
      'report' => $fixture->report->target_id,
      'referee' => $fixture->referee->value,
      'food' => $fixture->food->value,
      'food_notes' => $fixture->food_notes->value,
    ];
  }

  public function getFixturesForTeam(Node|int $team): array
  {
    $team = self::teamOrNid($team);

    return $this->entityTypeManager
      ->getStorage("nrfc_fixtures")
      ->loadMultiple($this->getQuery()
        ->condition('team_nid', $team->id())
        ->accessCheck(TRUE)
        ->execute());
  }

  private static function teamOrNid(Node|int $team): Node
  {
    if (!($team instanceof Node)) {
      $team = Node::load($team);
    }
    return $team;
  }

  private function getQuery(): \Drupal\Core\Entity\Query\QueryInterface
  {
    return $this->entityTypeManager
      ->getStorage('nrfc_fixtures')
      ->getQuery();
  }

  public function createOrUpdateFixture(array $fixtureData, Node $team): NRFCFixtures|bool
  {
    // TODO Validate fixture data
    try { # TODO better error handling
      $team_nid = $team->id();
      $delete = $fixtureData['delete'] ?? "";
      $nid = $fixtureData['nid'] ?? "";
      $date = date("Y-m-d", strtotime($fixtureData["date"] ?? ""));
      $ko = $fixtureData["ko"] ?? "";
      $ha = $fixtureData["home"] ?? "";
      $match_type = $fixtureData["match_type"] ?? "";
      $opponent = $fixtureData["opponent"] ?? "";
      $result = $fixtureData["result"] ?? "";
      $report = self::getReportId($fixtureData["report"] ?? "");
      $referee = $fixtureData["referee"] ?? "";
      $food = $fixtureData["food"] ?? "";
      $food_notes = $fixtureData["food_notes"] ?? "";

      $node = false;
      if ($nid) {
        /** @var $node NRFCFixtures */
        $node = $this->entityTypeManager
          ->getStorage('nrfc_fixtures')
          ->load($nid);
      }

      if ($node) {
        if ($delete) {
          $node->delete();
        } else {
          $node->date->value = $date;
          $node->ko->value = $ko;
          $node->home->value = $ha;
          $node->match_type->value = $match_type;
          $node->opponent->value = $opponent;
          $node->result->value = $result;
          $node->report->target_id = $report;
          $node->referee->value = $referee;
          $node->food->value = $food;
          $node->food_notes->value = $food_notes;
          $node->save();
          return $node;
        }
      } else {
        $this->entityTypeManager
          ->getStorage('nrfc_fixtures')
          ->create([
            'type' => 'nrfc_fixtures',
            'team_nid' => $team_nid,
            'date' => $date,
            'ko' => $ko,
            'home' => $ha,
            'match_type' => $match_type,
            'opponent' => $opponent,
            'result' => $result,
            'report' => $report,
            'referee' => $referee,
            'food' => $food,
            'food_notes' => $food_notes,
          ])->save();
          return $node;
      }
    } catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      return false;
    }
    return true;
  }

  public function deleteAll($team): void
  {
    // delete all fixtures
    $entities = $this->entityTypeManager
      ->getStorage("nrfc_fixtures")
      ->loadByProperties(array('team_nid' => $team->id()));
    foreach ($entities as $entity) {
      $entity->delete();
    }
  }

  private static function getReportId($report): ?int
  {
    $elements = explode(" ", $report);
    if (count($elements)) {
      return intval(preg_replace("/[^0-9 ]/", '', array_pop($elements)));
    }
    return null;
  }
}
