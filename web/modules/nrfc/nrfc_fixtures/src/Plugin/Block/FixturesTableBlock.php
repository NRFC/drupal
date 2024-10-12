<?php

declare(strict_types=1);

namespace Drupal\nrfc_fixtures\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\Entity\Node;
use Drupal\nrfc_fixtures\Entity\NRFCFixturesRepo;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a fixtures table block.
 *
 * @Block(
 *   id = "nrfc_fixtures_fixtures_table",
 *   admin_label = @Translation("Fixtures Table"),
 *   category = @Translation("NRFC"),
 * )
 */
final class FixturesTableBlock extends BlockBase implements ContainerFactoryPluginInterface {

  private mixed $routeMatch;

  /**
   * @var \Drupal\nrfc_fixtures\Entity\NRFCFixturesRepo
   */
  private NRFCFixturesRepo $nrfcFixturesRepo;

  /**
   * @var \Drupal\nrfc_fixtures\Plugin\Block\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $etmi;

  /**
   * Constructs the plugin instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    NRFCFixturesRepo $nrfcFixturesRepo,
    RouteMatchInterface $routeMatch
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->nrfcFixturesRepo = $nrfcFixturesRepo;
    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('nrfc_fixtures.repo'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $node = $this->routeMatch->getParameter('node');
    $build = [];
    if ($node instanceof Node) {
      $rows = $this->nrfcFixturesRepo->getFixturesForTeam($node);
      $build['content'] = [
        '#theme' => "nrfc_fixtures_team",
        "#fixtures" => $rows,
      ];
    }
    return $build;
  }

}
