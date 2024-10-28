const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const cssnano = require('gulp-cssnano');
const rename = require('gulp-rename');
const browserSync = require('browser-sync').create();

// Define your source directories
const paths = {
  scss: {
    nrfc_fixtures: 'web/modules/nrfc/nrfc_fixtures/scss/*.scss',
    nrfc_theme: 'web/themes/nrfc_theme/scss/*.scss'
  },
  css: {
    nrfc_fixtures: 'web/modules/nrfc/nrfc_fixtures/css',
    nrfc_theme: 'web/themes/nrfc_theme/css'
  },
  html: 'web/**/*.twig'
};

// Compile SASS to CSS
function styles() {
  return gulp.src([paths.scss.nrfc_fixtures, paths.scss.nrfc_theme]) // Use array for multiple sources
    .pipe(sass().on('error', sass.logError))
    .pipe(cssnano())
    .pipe(rename({ suffix: '.min' }))
    .pipe(gulp.dest(file => {
      // Determine the output path based on the source file path
      const srcFilePath = file.relative;
      if (srcFilePath.startsWith('modules/nrfc/nrfc_fixtures/scss')) {
        return paths.css.nrfc_fixtures;
      } else if (srcFilePath.startsWith('themes/nrfc_theme/scss')) {
        return paths.css.nrfc_theme;
      }
      // Default output path if none matched
      return paths.css.nrfc_theme; // Or handle as needed
    }))
    .pipe(browserSync.stream());
}

// Serve and watch files
function serve() {
  browserSync.init({
    proxy: 'localhost:81', // Change this to your local server's URL
  });

  // Watch the specific SCSS paths for changes
  gulp.watch([paths.scss.nrfc_fixtures, paths.scss.nrfc_theme], styles);
  gulp.watch(paths.html).on('change', browserSync.reload);
}

// Define the default task
exports.default = gulp.series(styles, serve);

