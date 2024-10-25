<?php

declare(strict_types=1);

namespace Drupal\Tests\nrfc_league_table_scraper\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\nrfc_league_table_scraper\Entity\TeamConfig;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MockContainer implements ContainerInterface
{

  public static function getRawData(): array
  {
    return [
      "base_url" => 'https://example.com/path/to/search-results',
      "xpath" => '//*[@id="league-table"]/div[2]/div/div/div/table/tbody/tr',
      "fragment" => '#tables',
      "nid_123_teamId" => 't123',
      "nid_123_competition" => 'c123',
      "nid_123_division" => 'd123',
      "nid_234_teamId" => 't234',
      "nid_234_competition" => 'c234',
      "nid_234_division" => '',
      "nid_345_teamId" => 't345',
      "nid_345_competition" => '',
      "nid_345_division" => '',
    ];
  }

  // Stub methods from ContainerInterface
  public function set(string $id, ?object $service)
  {
    // TODO: Implement set() method.
  }

  public function get(string $id, int $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE): ?object
  {
    // TODO: Implement get() method.
    return new MockContainer();
  }

  public function has(string $id): bool
  {
    // TODO: Implement has() method.
    return true;
  }

  public function initialized(string $id): bool
  {
    // TODO: Implement initialized() method.
    return true;
  }

  public function getParameter(string $name)
  {
    // TODO: Implement getParameter() method.
  }

  public function hasParameter(string $name): bool
  {
    // TODO: Implement hasParameter() method.
    return true;
  }

  public function setParameter(string $name, \UnitEnum|float|array|bool|int|string|null $value)
  {
    // TODO: Implement setParameter() method.
  }
}

/**
 * @covers TeamConfig::
 */
class TeamConfigTest extends UnitTestCase implements ServiceModifierInterface
{
  public function setUp(): void
  {
    \Drupal::setContainer(new MockContainer());
  }

  public function alter(ContainerBuilder $container)
  {
    $service_definition = $container->getDefinition('config.factory');
    $service_definition->setClass(MockContainer::class);
  }

  /**
   * @covers TeamConfig::makeUrl
   */
  public function testMakeUrl()
  {
    $teamConfig = TeamConfig::fromConfig();

    $teamConfig = new TeamConfig(123, "234");
    self::assertEquals("https://example.com/path/to/search-results?team=234&season=2024-2025#tables", $teamConfig->makeUrl());
    $teamConfig = new TeamConfig(123, "234", "345");
    self::assertEquals("https://example.com/path/to/search-results?team=234&competition=345&season=2024-2025#tables", $teamConfig->makeUrl());
    $teamConfig = new TeamConfig(123, "234", "345", "456");
    self::assertEquals("https://example.com/path/to/search-results?team=234&competition=345&division=456&season=2024-2025#tables", $teamConfig->makeUrl());
// Table
  }
}
