<?php

declare(strict_types=1);

namespace Drupal\nrfc_league_table_scraper\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Url;
use Drupal\nrfc_league_table\Plugin\Field\FieldType\LeagueTableItem;

/**
 * Plugin implementation of the 'nrfc_league_table_default' formatter.
 *
 * @FieldFormatter(
 *   id = "nrfc_league_table_default",
 *   label = @Translation("Default"),
 *   field_types = {"nrfc_league_table"},
 * )
 */
final class LeagueTableScraperDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $element = [];

    foreach ($items as $delta => $item) {

      if ($item->url) {
        $element[$delta]['url'] = [
          '#type' => 'item',
          '#title' => $this->t('URL'),
          'content' => [
            '#type' => 'link',
            '#title' => $item->url,
            '#url' => Url::fromUri($item->url),
          ],
        ];
      }

      if ($item->xpath) {
        $element[$delta]['xpath'] = [
          '#type' => 'item',
          '#title' => $this->t('XPatch'),
          '#markup' => $item->xpath,
        ];
      }

    }

    return $element;
  }

}
