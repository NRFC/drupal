<?php

namespace Drupal\nrfc_fixtures\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\nrfc_fixtures\Entity\NRFCFixturesRepo;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure site information settings for this site.
 *
 * @internal
 */
final class NrfcFixturesUploadForm extends ConfigFormBase implements ContainerInjectionInterface {

  // https://www.drupal.org/docs/drupal-apis/form-api/introduction-to-form-api
  const FORM_ID = 'nrfc_fixtures_upload_form';

  protected ?EntityTypeManagerInterface $entityTypeManager = NULL;

  protected ?NRFCFixturesRepo $nrfcFixturesRepo = NULL;

  private Renderer $renderer;

  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    Renderer $renderer,
    NRFCFixturesRepo $nrfcFixturesRepo,
    protected $typedConfigManager = NULL,
  ) {
    parent::__construct(
      $config_factory,
      $typedConfigManager
    );
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->nrfcFixturesRepo = $nrfcFixturesRepo;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('nrfc_fixtures.repo'),
      $container->get('config.typed'),
    );
  }

  private static function parseTime($timeString): string {
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
  public function getFormId() {
    return self::FORM_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $team = $this->getRouteMatch()->getParameter('team');

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

    $row_data = array_map(
      function($fixture) {
        return $this->nrfcFixturesRepo->fixtureToArray($fixture);
      }, $this->nrfcFixturesRepo->getFixturesForTeam($team)
    );
    foreach ($row_data as $key => $row) {
      if ($row['date']) {
        $row_data[$key]["date_as_string"] = date("d-m-Y", strtotime($row['date']));
      }
      if ($row['report']) {
        $node = Node::load($row["report"]);
        if ($node) {
          $row_data[$key]["report_as_string"] = $this->nrfcFixturesRepo->makeReportName($node);
        }
      }
    }

    // TODO - discuss? move this to the repo class and drop the entity manger from this one
    $nids = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->condition('type', 'match_report')
      ->accessCheck()
      ->execute();
    $reports = Node::loadMultiple($nids);

    $matchReports = [];
    foreach ($reports as $report) {
      $matchReports[] = $this->nrfcFixturesRepo->makeReportName($report);
    }

    $build = [
      '#theme' => 'admin.nrfc_fixtures_team',
      '#team' => $team->getTitle(),
      '#attached' => [
        'library' => [
          'nrfc_fixtures/nrfc_fixtures',
        ],
        'drupalSettings' => [
          'nrfc_fixtures' => [
            "rows" => $row_data,
            "match_reports" => $matchReports,
          ],
        ],
      ],
    ];

    $form['nrfc_fixtures_upload_form']['fixture_table'] = [
      "#markup" => $this->renderer->render($build),
    ];

    return $form;
  }

  public function title(Node $team) {
    return $team->getTitle() . " Fixtures Admin";
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void {
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

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $team = $this->getRouteMatch()->getParameter('team');
    $nid = $team->id();
    $node = Node::load($nid);

    if (!empty($form_state->getValue('clear_first'))) {
      if ($form_state->getValue('clear_first') === "clear first") {
        $this->nrfcFixturesRepo->deleteAll($node);
        $this->messenger()->addMessage(
          sprintf(
            "Cleared existing fixtures for %s.", $node->getTitle()
          ),
        );
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
        if ($cnt === 1) {
          // first row is a header row
          continue;
        }
        if (count($row) < 5) {
          $this->messenger()->addMessage(
            sprintf("Rejecting row %d, not enough columns(%d).", $cnt, count($row[0])),
          );
          continue;
        }
        $data = [];
        $data["date"] = $row[0];
        $data["ko"] = $row[1];
        $data["home"] = $row[2];
        $data["match_type"] = $row[3];
        $data["opponent"] = $row[4];
        $data["result"] = $row[5];
        $data["report"] = $row[6];
        $data["referee"] = $row[7];
        $data["food"] = $row[8];
        $data["food_notes"] = $row[9];
        $this->nrfcFixturesRepo->createOrUpdateFixture($data, $team);
      }
    }
  }

  protected function getEditableConfigNames(): array {
    return [];
  }

}
