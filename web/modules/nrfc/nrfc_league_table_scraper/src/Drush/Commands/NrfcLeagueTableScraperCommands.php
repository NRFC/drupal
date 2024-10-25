<?php

namespace Drupal\nrfc_league_table_scraper\Drush\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Core\Utility\Token;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class NrfcLeagueTableScraperCommands extends DrushCommands {

  /**
   * Command description here.
   */
  #[CLI\Command(name: 'nrfc_league_table_scraper:fetch')]
  #[CLI\Usage(name: 'nrfc_league_table_scraper:command-name foo', description: 'Usage description')]
  public function commandName() {
    nrfc_league_table_scraper_cron();
  }
}
