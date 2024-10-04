<?php

declare(strict_types=1);

namespace Drupal\nrfc_fixtures\Entity;

use Drupal\Core\Entity\Annotation\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\nrfc_fixtures\NRFCFixturesInterface;

/**
 * Defines the nrfc fixtures entity class.
 *
 * @ContentEntityType(
 *   id = "nrfc_fixtures",
 *   label = @Translation("NRFC Fixtures"),
 *   label_collection = @Translation("NRFC Fixturess"),
 *   label_singular = @Translation("nrfc fixtures"),
 *   label_plural = @Translation("nrfc fixturess"),
 *   label_count = @PluralTranslation(
 *     singular = "@count nrfc fixturess",
 *     plural = "@count nrfc fixturess",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\nrfc_fixtures\NRFCFixturesListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\nrfc_fixtures\Form\NRFCFixturesForm",
 *       "edit" = "Drupal\nrfc_fixtures\Form\NRFCFixturesForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\nrfc_fixtures\Routing\NRFCFixturesHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "nrfc_fixtures",
 *   admin_permission = "administer nrfc_fixtures",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/nrfc-fixtures",
 *     "add-form" = "/nrfc-fixtures/add",
 *     "canonical" = "/nrfc-fixtures/{nrfc_fixtures}",
 *     "edit-form" = "/nrfc-fixtures/{nrfc_fixtures}",
 *     "delete-form" = "/nrfc-fixtures/{nrfc_fixtures}/delete",
 *     "delete-multiple-form" = "/admin/content/nrfc-fixtures/delete-multiple",
 *   },
 * )
 *
 * @property string $date
 * @property string $ko;
 * @property string $home;
 * @property string $match_type;
 * @property string $opponent;
 * @property string $result;
 * @property string $report;
 * @property string $referee;
 * @property string $food;
 * @property string $food_notes;
 */
final class NRFCFixtures extends ContentEntityBase implements NRFCFixturesInterface
{

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
  {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['team_nid'] = BaseFieldDefinition::create('integer')->setLabel(t('Team NID'))->setRequired(TRUE);
    $fields['date'] = BaseFieldDefinition::create('datetime')->setLabel(t('Date'))->setRequired(TRUE);
    $fields['ko'] = BaseFieldDefinition::create('string')->setLabel(t('Kick off'));
    $fields['home'] = BaseFieldDefinition::create('list_string')->setLabel(t('Home/Away'))->setRequired(TRUE);
    $fields['match_type'] = BaseFieldDefinition::create('list_string')->setLabel(t('Match type'));
    $fields['opponent'] = BaseFieldDefinition::create('string')->setLabel(t('Opponent'))->setRequired(TRUE);
    $fields['result'] = BaseFieldDefinition::create('string')->setLabel(t('Result'));
    $fields['report'] = BaseFieldDefinition::create('entity_reference')->setLabel(t('Report'));
    $fields['referee'] = BaseFieldDefinition::create('list_string')->setLabel(t('Referee'));
    $fields['food'] = BaseFieldDefinition::create('integer')->setLabel(t('Food'));
    $fields['food_notes'] = BaseFieldDefinition::create('string')->setLabel(t('Food Notes'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the nrfc fixtures was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the nrfc fixtures was last edited.'));

    return $fields;
  }

  public function __toString(): string
  {
    return sprintf(
      "%s nid=%d team_id=%d date=%s ko=%s ha=%s match_type=%s opponent=%s result=%s report_id=%d referee=%s food=%s food_notes=%s",
      __CLASS__,
      $this->id(),
      $this->team_nid->value,
      $this->date->value,
      $this->ko->value,
      $this->home->value,
      $this->match_type->value,
      $this->opponent->value,
      $this->result->value,
      $this->report->target_id,
      $this->referee->value,
      $this->food->value,
      $this->food_notes->value,
    );
  }
}
