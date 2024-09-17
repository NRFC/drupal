<?php

declare(strict_types=1);

namespace Drupal\nrfc_league_table_scraper\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\nrfc_league_table\Plugin\Field\FieldType\LeagueTableItem;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Defines the 'nrfc_league_table' field widget.
 *
 * @FieldWidget(
 *   id = "nrfc_league_table",
 *   label = @Translation("League Table"),
 *   field_types = {"nrfc_league_table"},
 * )
 */
final class LeagueTableScraperWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {

    $element['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $items[$delta]->url ?? NULL,
    ];

    $element['xpath'] = [
      '#type' => 'textfield',
      '#title' => $this->t('XPath'),
      '#default_value' => $items[$delta]->xpath ?? NULL,
    ];

    $element['#theme_wrappers'] = ['container', 'form_element'];
    $element['#attributes']['class'][] = 'container-inline';
    $element['#attributes']['class'][] = 'nrfc-league-table-elements';
    $element['#attached']['library'][] = 'nrfc_league_table_scraper/nrfc_league_table_scraper';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state): array|bool {
    $element = parent::errorElement($element, $error, $form, $form_state);
    if ($element === FALSE) {
      return FALSE;
    }
    $error_property = explode('.', $error->getPropertyPath())[1];
    return $element[$error_property];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
    foreach ($values as $delta => $value) {
      if ($value['url'] === '') {
        $values[$delta]['url'] = NULL;
      }
      if ($value['xpath'] === '') {
        $values[$delta]['xpath'] = NULL;
      }
    }
    return $values;
  }

}
