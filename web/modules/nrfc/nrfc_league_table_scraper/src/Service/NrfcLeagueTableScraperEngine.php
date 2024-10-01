<?php

namespace Drupal\nrfc_league_table_scraper\Service;

use DOMDocument;
use DOMXPath;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\nrfc_league_table_scraper\Entity\TeamConfig;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Configure site information settings for this site.
 *
 * @internal
 */
class NrfcLeagueTableScraperEngine
{
  private Client $httpsClient;
  private Connection $connection;
  private LoggerChannel $l;

  public function __construct(
    Client        $httpsClient,
    Connection    $dbConnection,
    LoggerChannel $logger,
  )
  {
    $this->httpsClient = $httpsClient;
    $this->connection = $dbConnection;
    $this->l = $logger;
  }

  public function updateAll(): void
  {
    $teams = TeamConfig::fromConfig();
//    $this->l->debug("Update %count teams", ["%count" => count($teams)]);
    foreach ($teams as $team) {
      $this->updateTeam($team);
    }
  }

  public function updateTeam(TeamConfig $team): void
  {
//    $this->l->debug("Updating " . $team);
    $node = \Drupal\node\Entity\Node::load($team->getTeamNid());
    $this->fetch($team);
  }

  public function fetch(TeamConfig $team): void
  {
    try {
      $url = $team->makeUrl();
      $this->l->debug("Fetching page from " . $url);
      $response = $this->httpsClient->get($url);
      $body = $response->getBody()->getContents();
//      $this->l->debug("RFU Response length " . strlen($body));

      $dom = new DOMDocument();
      libxml_use_internal_errors(true);
      $dom->loadHTML($body);
      $xpath = new DOMXPath($dom);
      $rows = $xpath->query(TeamConfig::getXPath());
//      $this->l->debug("Found " . count($rows) . " rows");
      foreach ($rows as $row) {
        $cells = $row->childNodes;
        // TODO figure out these settings from the table header
        if (count($cells) >= 11) { // technically 10 is required but <11 means we don't have valid data
          $teamName = trim($cells[3]->nodeValue);
          $this->cleanRow($team->getTeamNid(), $teamName);
          $this->addRow(
            $team->getTeamNid(),
            $teamName,
            trim($cells[7]->nodeValue),
            trim($cells[9]->nodeValue),
            trim($cells[11]->nodeValue),
            trim($cells[13]->nodeValue),
            trim($cells[15]->nodeValue),
            trim($cells[19]->nodeValue),
            trim($cells[21]->nodeValue)
          );
        }
      }
    } catch (GuzzleException $e) {
      $this->l->error("Unable to fetch league results page ");
    }
  }

  /**
   * @throws Exception
   */
  private function cleanRow(int $nid, string $teamName): void
  {
    try {
//      $this->l->debug("Cleaning row: nid=" . $nid . ", " . $teamName);
      $result = $this->connection
        ->delete('nrfc_league_table_scraper_table_data')
        ->condition('team_nid', $nid)
        ->condition('team_name', $teamName)
        ->execute();
    } catch (Exception $e) {
      $this->l->warning("Error clearing league row, team_nid=%team_nid team_name=%team_name", [
        '%team_nid' => $nid,
        '%team_name' => $teamName,
      ]);
    }
  }

  /**
   * @throws Exception
   */
  private function addRow($nid, $teamName, $win, $draw, $lose, $points_for, $points_against, $try_bonus, $lose_bonus): void
  {
//    $this->l->debug("Inserting row: nid=" . $nid . ", " . $teamName);
    try {
      $result = $this->connection->insert('nrfc_league_table_scraper_table_data')
        ->fields([
          'team_nid' => $nid,
          'team_name' => $teamName,
          'win' => $win,
          'lose' => $lose,
          'draw' => $draw,
          'points_for' => $points_for,
          'points_against' => $points_against,
          'try_bonus' => $try_bonus,
          'lose_bonus' => $lose_bonus,
        ])
        ->execute();
    } catch (Exception $e) {
      $this->l->error(
        "Unable to insert league results page, " .
        "nid=%nid " .
        "teamName=%teamName " .
        "win=%win " .
        "lose=%lose " .
        "draw=%draw " .
        "points_for=%points_for " .
        "points_against=%points_against " .
        "try_bonus=%try_bonus " .
        "lose_bonus=%lose_bonus", [
        "%nid" => $nid,
        "%teamName" => $teamName,
        "%win" => $win,
        "%lose" => $lose,
        "%draw" => $draw,
        "%points_for" => $points_for,
        "%points_against" => $points_against,
        "%try_bonus" => $try_bonus,
        "%lose_bonus" => $lose_bonus
      ]);
    }
  }
}
