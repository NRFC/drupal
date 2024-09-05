/**
 * @file
 * Global utilities.
 *
 */
(function($, Drupal) {

  'use strict';

  Drupal.behaviors.nrfc_barrio = {
    attach: function(context, settings) {

      // Custom code here
      // <div class="field field--name-field-photographs field--type-image field--label-visually_hidden">
      // <img loading="lazy" src="/sites/default/files/styles/slideshow_1000x600/public/teams/carousel/new_IMG_7897.png?itok=P9GPUSm8" width="1000" height="600" alt="pic1" class="image-style-slideshow-1000x600">
      const slideshowimages = $(".field--name-field-photographs img");
    }
  };

})(jQuery, Drupal);
