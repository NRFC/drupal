/**
 * @file
 * Global utilities.
 *
 */
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.nrfc_barrio = {
    attach: function (context, settings) {

      function slider(images) {
        let currentIndex = 0;
        if (imageArray.length > 0) {
          imageArray[currentIndex].style.display = "block";
        }

        function showNextImage() {
          imageArray[currentIndex].style.display = "none";
          currentIndex = (currentIndex + 1) % imageArray.length;
          imageArray[currentIndex].style.display = "block";
        }

        setInterval(showNextImage, 5000);
      }

      const imageList = document.querySelectorAll(".field--name-field-photographs img");
      const imageArray = Array.from(imageList);
      if (imageList.length > 0) {
        imageArray.forEach((img, index) => {
          img.style.display = "none";
        });
        slider(imageArray);
      }
    }
  };

  // <div class="field field--name-field-photographs field--type-image field--label-visually_hidden">
  // <img loading="lazy" src="/sites/default/files/styles/slideshow_1000x600/public/teams/carousel/new_IMG_7897.png?itok=P9GPUSm8" width="1000" height="600" alt="pic1" class="image-style-slideshow-1000x600">
  // const slideshowimages = jQuery(".field--name-field-photographs img");
  // if (slideshowimages.length > 0) {
  //   console.log("HERE");
  // }

})(jQuery, Drupal);

