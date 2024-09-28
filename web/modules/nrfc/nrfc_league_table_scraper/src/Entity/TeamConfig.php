<?php

namespace Drupal\nrfc_league_table_scraper\Entity;


/**
 * Configure site information settings for this site.
 *
 * @internal
 */
class TeamConfig
{
  private static string $base_url = "";
  private static string $xpath = "";
  private static string $fragment = "";

  private int $teamNid;
  private string $teamId;
  private string $competition;
  private string $division;

  /**
   * @param int $teamNid
   * @param string $competition
   * @param string $division
   * @param string $teamId
   */
  public function __construct(int $teamNid, string $teamId, string $competition = "", string $division = "")
  {
    $this->competition = $competition;
    $this->division = $division;
    $this->teamId = $teamId;
    $this->teamNid = $teamNid;
  }

  public static function fromConfig(): array
  {
    /** @var TeamConfig[] $teams */
    $teams = [];

    $config = \Drupal::config('nrfc_league_table_scraper.scraper_settings')->getRawData();
    $teamData = [];
    foreach ($config as $key => $value) {
      if (str_starts_with($key, 'nid')) {
        $elements = explode("_", $key);
        if (count($elements) !== 3) {
          \Drupal::logger(__NAMESPACE__ . '::' . __FUNCTION__)->warning("Bad team config data: " . $key);
          continue;
        }
        $teamId = $elements[1];
        if (array_key_exists($teamId, $teamData)) {
          $teamData[$teamId][$elements[2]] = $value;
        } else {
          $teamData[$teamId] = [$elements[2] => $value];
        }
      } else if (str_starts_with($key, 'base_url')) {
        self::$base_url = $value;
      } else if (str_starts_with($key, 'xpath')) {
        self::$xpath = $value;
      } else if (str_starts_with($key, 'fragment')) {
        self::$fragment = $value;
      }
    }
    foreach ($teamData as $key => $team_config) {
      $team = self::fromConfigEntry($key, $team_config);
      if ($team) {
        $teams[] = $team;
      }
    }
    return $teams;
  }

  public static function fromConfigEntry($nid, array $team): TeamConfig|bool
  {
    if (!(array_key_exists("teamId", $team) && !empty(trim($team["teamId"])))) {
      return false;
    }
    return new TeamConfig(
      $nid,
      $team['teamId'],
      $team['competition'],
      $team['division']
    );
  }

  public static function getXPath(): string
  {
    return self::$xpath;
  }

  public function getCompetition(): string
  {
    return $this->competition;
  }

  public function setCompetition(string $competition): void
  {
    $this->competition = $competition;
  }

  public function getDivision(): string
  {
    return $this->division;
  }

  public function setDivision(string $division): void
  {
    $this->division = $division;
  }

  public function getTeamId(): string
  {
    return $this->teamId;
  }

  public function setTeamId(string $teamId): void
  {
    $this->teamId = $teamId;
  }

  public function getTeamNid(): int
  {
    return $this->teamNid;
  }

  public function setTeamNid(int $teamNid): void
  {
    $this->teamNid = $teamNid;
  }

  public function makeUrl()
  {
    $url = sprintf("%s?team=%d", self::$base_url, $this->teamId);
    if (!empty($this->competition)) {
      $url .= sprintf("&competition=%s", $this->competition);
    }
    if (!empty($this->division)) {
      $url .= sprintf("&division=%s", $this->division);
    }
    $url .= sprintf("&season=%s", self::getSeason());
    $url .= self::$fragment;
    return $url;
  }

  private static function getSeason(): string
  {
    if (date(" % m") < 8) {
      return sprintf(
        "%d-%d",
        intval(date("Y")),
        intval(date("Y")) + 1
      );
    } else {
      return sprintf(
        "%d-%d",
        intval(date("Y") - 1),
        intval(date("Y"))
      );
    }
  }

  public function __toString(): string
  {
    return sprintf("TeamConfig: nid=%d, RFU[id=%s, comp=%s, div=%s]",
      $this->teamNid,
      $this->teamId,
      $this->competition,
      $this->division,
    );
  }
}
