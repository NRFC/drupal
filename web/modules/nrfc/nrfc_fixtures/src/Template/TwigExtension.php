<?php

namespace Drupal\nrfc_fixtures\Template;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension providing custom functionalities.
 *
 * @package Drupal\nrfc_fixtures\Template
 */
class TwigExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'nrfc_fixtures';
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new TwigFunction('nrfc_stripe', [
        $this,
        'getStripe'
      ]),
    ];
  }

  /**
   * Returns odd or even string for a table row stripe.
   *
   * @return string
   *   odd/even
   */
  public function getStripe(int $index = 0): string {
    return ($index % 2 === 0) ? 'even' : 'odd';
  }
}
