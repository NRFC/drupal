<?php

namespace Drupal\nrfc_fixtures\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\nrfc_fixtures\Entity\NRFCFixturesRepo;

/**
 * Defines the admin section controller.
 */
final class FixturesController extends ControllerBase {

  private LoggerChannel $logger;

  private NRFCFixturesRepo $nrfcFixturesRepo;

  public function __construct(
    LoggerChannel $logger,
    NRFCFixturesRepo $nrfcFixturesRepo
  ) {
    $this->logger = $logger;
    $this->nrfcFixturesRepo = $nrfcFixturesRepo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(\Symfony\Component\DependencyInjection\ContainerInterface $container) {
    return new static(
      $container->get('logger.channel_nrfc'),
      $container->get('nrfc_fixtures.repo'),
    );
  }

  public function all(): array {
    $fixtures = $this->nrfcFixturesRepo->getAll();
    return [
      '#theme' => 'nrfc_fixtures_all',
      '#fixtures' => $fixtures,
    ];
  }

}
