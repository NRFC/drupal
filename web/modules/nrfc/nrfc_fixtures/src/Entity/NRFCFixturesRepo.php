<?php

declare(strict_types=1);

namespace Drupal\nrfc_fixtures\Entity;

use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\node\Entity\Node;
use Drupal\nrfc\Service\NRFC;
use Drupal\nrfc\Unit\MockTerm;
use Drupal\taxonomy\Entity\Term;
use Exception;

class NRFCFixturesRepo {

  private EntityTypeManagerInterface $entityTypeManager;
  private LoggerChannel $logger;

  private array $termList = [];


  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LoggerChannel $logger

  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
  }

  public function makeReportName(Node $report): ?string {
    return sprintf("%s - %s [%d]", date("d/m/y", $report->getChangedTime()), $report->getTitle(), $report->id());
  }

  public function fixturesToArray(array $fixtures): array {
    $data = [];
    foreach ($fixtures as $fixture) {
      $data[] = $this->fixtureToArray($fixture);
    }
    return $data;
  }

  public function fixtureToArray(NRFCFixtures $fixture): array {
    return [
      'nid' => $fixture->id(),
      'team_nid' => $fixture->team_nid,
      'date' => $fixture->date->value,
      'ko' => $fixture->ko->value,
      'home' => $fixture->home->value,
      'match_type' => $fixture->match_type->value,
      'opponent' => $fixture->opponent->value,
      'result' => $fixture->result->value,
      'report' => $fixture->report->target_id == 0 ? "" : $fixture->report->target_id,
      'referee' => $fixture->referee->value,
      'food' => $fixture->food->value,
      'food_notes' => $fixture->food_notes->value,
    ];
  }

  public function getFixturesForTeam(Node|int $team): array {
    $team = self::teamOrNid($team);
    return $this->entityTypeManager->getStorage("nrfc_fixtures")
      ->loadMultiple($this->getQuery()
        ->condition('team_nid', $team->id())
        ->accessCheck(TRUE)
        ->execute());
  }

  private static function teamOrNid(Node|int $team): Node {
    if (!($team instanceof Node)) {
      $team = Node::load($team);
    }
    return $team;
  }

  public function createOrUpdateFixture(array $fixtureData, Node $team): NRFCFixtures|bool {
    // TODO Refactor all this, Ocams razor, better validation and error handling
    try {
      $team_nid = $team->id();
      $delete = $fixtureData['delete'] ?? "";
      $nid = $fixtureData['nid'] ?? "";
      $date = date("d/m/Y", strtotime($fixtureData["date"] ?? ""));
      $ko = $fixtureData["ko"] ?? "";
      $ha = strtolower($fixtureData["home"]) === "a" ? "Away" : "Home";
      $match_type = $this->parseType($fixtureData["match_type"]);
      $opponent = $fixtureData["opponent"] ?? "";
      $result = $fixtureData["result"] ?? "";
      $report = $this->getReportId($row["report"] ?? "");
      $referee = $fixtureData["referee"] ?? "";
      $food = $fixtureData["food"] ?? "";
      $food_notes = $fixtureData["food_notes"] ?? "";

      $node = FALSE;
      if ($nid) {
        /** @var $node NRFCFixtures */
        $node = $this->entityTypeManager->getStorage('nrfc_fixtures')
          ->load($nid);
      }

      if ($node) {
        if ($delete) {
          $node->delete();
        }
        else {
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
      }
      else {
        $this->entityTypeManager->getStorage('nrfc_fixtures')->create([
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
    }
    catch (Exception $e) {
      $this->logger->error($e->getMessage());
      return FALSE;
    }
    return TRUE;
  }

  public function getReportId(string $report): ?int {
    $elements = explode(" ", $report);
    if (count($elements)) {
      return intval(preg_replace("/[^0-9 ]/", '', array_pop($elements)));
    }
    return NULL;
  }

  public function deleteAll($team): void {
    // delete all fixtures
    $entities = $this->entityTypeManager->getStorage("nrfc_fixtures")
      ->loadByProperties(['team_nid' => $team->id()]);
    foreach ($entities as $entity) {
      $entity->delete();
    }
  }


  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getFixturesByTermName(string|Node $term): array {
    // Get term
    if (!$term instanceof Term) {
      $term = $this->caseInsensitiveTeamSectionSearch($term);
    }
    // Get list of tids, this will include children where appropriate
    $children = $this->entityTypeManager
      ->getStorage("taxonomy_term")
      ->loadTree(
        NRFC::TEAM_SECTION_ID,
        $term->id(),
        null,
        true
      );
    $tids = [
      $term->id(),
      ...array_map(
        function($t) {
          return $t->id();
        },
        $children
      ),
    ];

    // Get all teams under that term
    /*
      TODO - this query doesn't work
      I can't get the reference term bit to work. If we can fix that we can
      replace the programmatic filter for teams belonging to term.
    */
    $teams = Node::loadMultiple(
      $this->entityTypeManager
        ->getStorage("node")
        ->getQuery()
        ->accessCheck()
        ->condition('status', 1)
        ->condition('type', 'team')
        ->execute()
    );
    // for each team get their fixtures
    $fixtures = [];
    // Filter teams for those with a term or parent term that matches. See comment above, we should be able to fold this into the DB query
    foreach ($teams as $index => $team) {
      $sections = $team->get('field_section')->getValue();
      if (count($sections) === 0) {
        $this->logger->error(sprintf(
          "No team section found for term '%s'.",
          $team->getTitle())
        );
        continue;
      }
      else {
        if (count($sections) > 1) {
          $this->logger->warning(sprintf(
            "Multiple sections found for term '%s'.",
            $team->getTitle())
          );
        }
      }
      $tid = array_pop($sections[0]);
      if (!in_array($tid, $tids)) {
        unset($teams[$index]);
      }
      else {
        $fixtures[$team->getTitle()] = $this->getFixturesForTeam($team);
      }
    }
    return $fixtures;
  }

  public function caseInsensitiveTeamSectionSearch($name): Term|MockTerm|null { // FIXME - get rid of MockTerm
    if (count($this->termList) == 0) {
      /* @var Term[] $terms */
      $terms = $this->entityTypeManager->getStorage('taxonomy_term')
        ->loadTree(NRFC::TEAM_SECTION_ID);
      foreach ($terms as $term) {
        $this->termList[strtolower($term->name)] = $term;
      }
    }
    if (in_array($name, array_keys($this->termList))) {
      return Term::load($this->termList[$name]->tid);
    }
    $this->logger->warning("No section found for " . $name);
    return NULL;
  }

  public function parseType(string $typeString): string {
    $type = strtoupper($typeString);
    return match ($type) {
      "L" => "League",
      "F" => "Friendly",
      "FE" => "Festival",
      "T" => "Tournament",
      "NC" => "National Cup",
      "CC" => "County Cup",
      "C" => "Cup",
      default => "",
    };
  }


}
