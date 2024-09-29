<?php


namespace Drupal\nrfc\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Defines the admin section controller.
 */
class AdminSectionController extends ControllerBase
{

  /**
   * Returns a render-able array for the admin page.
   */
  public function adminPage()
  {
    return [
      '#markup' => '<p>Welcome to the Custom Admin Section!</p>',
    ];
  }

}
