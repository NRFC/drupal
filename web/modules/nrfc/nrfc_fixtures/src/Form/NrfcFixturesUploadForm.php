<?php

namespace Drupal\nrfc_fixtures\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure site information settings for this site.
 *
 * @internal
 */
final class NrfcFixturesUploadForm extends FormBase
{
  // https://www.drupal.org/docs/drupal-apis/form-api/introduction-to-form-api
  const FORM_ID = 'nrfc_fixtures_upload_form';

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

    $form['nrfc_fixtures_upload_form'] = [
      '#type' => 'details',
      '#title' => $this->t('Bulk Functions'),
      '#open' => TRUE,
    ];

    $form['nrfc_fixtures_upload_form']['cvs_file'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload CSV File'),
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
      '#prefix'=>'<div class="inputHolder"><p class="sub-title-1">',
      '#suffix'=>'</p></div>',
      '#url' => \Drupal\Core\Url::fromRoute('nrfc_fixtures.admin_page'),
    ];

    $form['nrfc_fixtures_upload_form']['get_template'] = [
      '#title' => $this->t('Download an empty template as a CSV file.'),
      '#type' => 'link',
      '#prefix'=>'<div class="inputHolder"><p class="sub-title-1">',
      '#suffix'=>'</p></div>',
      '#url' => \Drupal\Core\Url::fromRoute('nrfc_fixtures.admin_page'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    // TODO: Implement submitForm() method.
  }
}
