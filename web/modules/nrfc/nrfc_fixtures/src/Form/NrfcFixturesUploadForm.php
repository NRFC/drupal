<?php

namespace Drupal\nrfc_fixtures\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\nrfc_fixtures\Entity\NRFCFixtures;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure site information settings for this site.
 *
 * @internal
 */
final class NrfcFixturesUploadForm extends FormBase
{
  // https://www.drupal.org/docs/drupal-apis/form-api/introduction-to-form-api
  const FORM_ID = 'nrfc_fixtures_upload_form';

  private EntityTypeManagerInterface $entityTypeManager;

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $routeMatch)
  {
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $routeMatch;
  }

  private static function parseType($typeString): string
  {
    $type = strtoupper($typeString);
    return match ($type) {
      "L" => "League",
      "F" => "Friendly",
      "Fe" => "Festival",
      "T" => "Tournament",
      "NC" => "National Cup",
      "CC" => "County Cup",
      "C" => "Cup",
      default => "",
    };
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return self::FORM_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $team = $this->routeMatch->getParameter('team');

    $form['nrfc_fixtures_upload_form'] = [
      '#type' => 'details',
      '#title' => $this->t('Bulk Functions'),
      '#open' => TRUE,
    ];

    $form['nrfc_fixtures_upload_form']['csv_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload CSV File'),
      '#upload_location' => 'public://fixtures/' . $team->getTitle() . 'csv',
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#description' => $this->t(
        'Upload a set of new fixtures from a csv file.'
      ),
    ];

    $form['nrfc_fixtures_upload_form']['clear_first'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Clear all existing data'),
      '#description' => $this->t(
        'Type the words "clear first" to ensure you <b>REALLY</b> want to nix the fixtures for this team. There is no undo function.'
      ),
    ];

    $form['nrfc_fixtures_upload_form']['submit'] = [
      '#value' => $this->t('Upload'),
      '#type' => 'submit',
    ];

    $form['nrfc_fixtures_upload_form']['get_existing'] = [
      '#title' => $this->t('Export the existing fixtures as a CSV file'),
      '#type' => 'link',
      '#prefix' => '<div class="inputHolder"><p class="sub-title-1">',
      '#suffix' => '</p></div>',
      '#url' => \Drupal\Core\Url::fromRoute(
        'nrfc_fixtures.admin_page.download',
        [
          "team" => $team->id(),
        ]
      ),
    ];

    $form['nrfc_fixtures_upload_form']['get_template'] = [
      '#title' => $this->t('Download an empty template as a CSV file.'),
      '#type' => 'link',
      '#prefix' => '<div class="inputHolder"><p class="sub-title-1">',
      '#suffix' => '</p></div>',
      '#url' => \Drupal\Core\Url::fromRoute('nrfc_fixtures.admin_page.template'),
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void
  {
    $node = $this->routeMatch->getParameter('team');
    if (!$node instanceof Node) {
      $this->messenger()->addMessage(
        "Fatal error, I can't find the team for this action.",
        MessengerInterface::TYPE_ERROR
      );
      $form_state->setRebuild();
      return;
    }
    $nid = $node->id();

    if (!empty($form_state->getValue('clear_first')) && $form_state->getValue('clear_first') !== "clear first") {
      $form_state->setError(
        $form['nrfc_fixtures_upload_form']['clear_first'],
        'You must enter the text "clear first" if you want to delete all the existing fixtures. Or clear the text box to just add records.'
      );
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $team = $this->routeMatch->getParameter('team');
    $nid = $team->id();

    if (!empty($form_state->getValue('clear_first'))) {
      if ($form_state->getValue('clear_first') === "clear first") {
        // delete all fixtures
        $entities = $this->entityTypeManager
          ->getStorage("nrfc_fixtures")
          ->loadByProperties(array('type' => 'nrfc_fixtures'));
        foreach ($entities as $entity) {
          $entity->delete();
        }
      }
    }

    $form_file = $form_state->getValue('csv_file');
    if (!empty($form_file[0])) {
      $file = File::load($form_file[0]);
      $file->setPermanent();
      $file->save();
      $fh = fopen($file->getFileUri(), "r");
      $cnt = 0;
      while ($row = fgetcsv($fh)) {
        $cnt += 1;
        if (count($row) < 5) {
          $this->messenger()->addMessage(
            sprintf("Rejecting row %d, not enough columns(%d).", $cnt, count($row[0])),
          );
          continue;
        }
        $date = date("d-m-y", strtotime(($row[0])));
        $ko = self::parseTime($row[1]);
        $ha = strtolower($row[2]) === "a" ? "Away" : "Home";
        $type = self::parseType($row[3]);
        $opponent = $row[4];
        if ($date === "01-01-70" || empty($opponent)) {
          $this->messenger()->addMessage(
            sprintf(
              "Date and opponent are mandatory, '%s', '%s', '%s', '%s', '%s'.",
              $row[0], $row[1], $row[2], $row[3], $row[4]
            )
          );
          continue;
        }
        $this->logger(__CLASS__)->info(
            sprintf(
              "Creating row: '%s', '%s', '%s', '%s', '%s'.",
              $date, $ko, $ha, $type, $opponent
            )
        );
        $node = NRFCFixtures::create([
          'type' => 'nrfc_fixtures',
          'team_nid' => $team->id(),
          'date' => $date,
          'ko' => $ko,
          'home' => $ha,
          'match_type' => $type,
          'opponent' => $opponent,
        ]);
        $node->save();
      }
    }
  }

  private static function parseTime($timeString): string
  {
    $data = explode(':', $timeString);
    if (count($data) < 2) {
      return "";
    }
    $hour = $data[0];
    $minute = $data[1];
    if (!$hour || !$minute || intval($hour) < 0 || intval($hour) > 23 || intval($minute) < 0 || intval($minute) > 59) {
      return "";
    }
    return trim($hour) . ":" . trim($minute);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
    );
  }
}
