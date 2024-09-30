<?php

namespace Drupal\nrfc_league_table_scraper\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\Entity\Node;

/**
 * Provides a 'Hello' Block.
 */
#[Block(
  id: "nrfc_league_table_block",
  admin_label: new TranslatableMarkup("League Table"),
  category: new TranslatableMarkup("NRFC"),
)]
final class NrfcLeagueTableBlock extends BlockBase implements ContainerFactoryPluginInterface
{
  protected RouteMatchInterface $routeMatch;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $routeMatch)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $routeMatch;
  }

  public static function create(\Symfony\Component\DependencyInjection\ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array
  {
    $node = $this->routeMatch->getParameter('node');
    if ($node instanceof Node) {
      // Get the node ID.
      $nid = $node->id();

      $result = Database::getConnection()
        ->select('nrfc_league_table_scraper_table_data')
        ->fields('nrfc_league_table_scraper_table_data', [])
        ->condition('team_nid', $nid)
        ->execute();

      $rows = [];
      foreach ($result as $row) {
        $rows[] = new TableRow($row);
      }

      usort($rows, function ($a, $b) {
        return $b->total_points - $a->total_points;
      });

      return [
        '#theme' => 'nrfc_league_table_scraper',
        '#rows' => $rows,
        '#cache' => [
          'max-age' => 0,
        ],
      ];
    }
    return [];
  }
  // https://www.drupal.org/docs/creating-modules/creating-custom-blocks/create-a-custom-block-plugin
}

class TableRow
{
  public string $teamName;
  public int $win;
  public int $lose;
  public int $draw;
  public int $points_for;
  public int $points_against;
  public int $try_bonus;
  public int $lose_bonus;

  public int $played;
  public int $total_points;
  public int $points_diff;

  public function __construct($row)
  {
    $this->teamName = $row->team_name;
    $this->win = $row->win;
    $this->lose = $row->lose;
    $this->draw = $row->draw;
    $this->points_for = $row->points_for;
    $this->points_against = $row->points_against;
    $this->try_bonus = $row->try_bonus;
    $this->lose_bonus = $row->lose_bonus;

    $this->played = $this->win + $this->lose + $this->draw;
    $this->total_points = $this->win * 4 + $this->draw + $this->try_bonus + $this->lose_bonus;
    $this->points_diff = $this->points_for - $this->points_against;
  }
}
