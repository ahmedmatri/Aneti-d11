// --------------------------------------------------------------------
// Plugins
// --------------------------------------------------------------------

var gulp           = require('gulp'),
    sass           = require('gulp-sass'),
    concat         = require('gulp-concat'),
    watch          = require("gulp-watch"),
    plumber        = require('gulp-plumber'),
    cleanCSS       = require('gulp-clean-css'),
    autoprefixer   = require('gulp-autoprefixer'),
    csso           = require('gulp-csso'),

    imagemin       = require("gulp-imagemin"),
    pngquant       = require("imagemin-pngquant"),

    sourcemaps     = require('gulp-sourcemaps'),
    runSequence    = require('gulp-run-sequence'),
    clean          = require('gulp-clean');


var AUTOPREFIXER_BROWSERS = [
  'last 2 version',
  'ie >= 9',
  'ie_mob >= 10',
  'ff >= 30',
  'chrome >= 34',
  'safari >= 7',
  'opera >= 23',
  'ios >= 7',
  'android >= 4.4',
  'bb >= 10'
];

var onError = function(err) {
    console.log(err);
    this.emit('end');
};
    
// --------------------------------------------------------------------
// Settings Path
// --------------------------------------------------------------------
path = {
    output: {
        style: 'dist/css/',
        img: 'dist/images/',
        fonts: 'dist/fonts/'
    },
    input: {
        style: 'assets/sass/style.scss',
        styleRtl: 'assets/sass/style-rtl.scss',
        img: 'assets/images/**/*.*',
        fonts: 'assets/fonts/**/*.*'
    },
    watch: {
        style: 'assets/sass/**/*.*',
        styleRtl: 'assets/sass/style-rtl.scss',
        img: 'assets/images/**/*.*',
        fonts: 'assets/fonts/**/*.*'
    },
    clean: './dist'
};


/*BUILD
=================================================================================*/

// Sass build
gulp.task('style:build', function() {
    return gulp.src(path.input.style)
    .pipe(sourcemaps.init())
    .pipe(plumber({
        errorHandler: onError
    }))
    .pipe(sass())
    .pipe(autoprefixer({
        browsers: AUTOPREFIXER_BROWSERS,
        cascade: false
    }))
    .pipe(csso())
    .pipe(cleanCSS())
    .pipe(concat('style.min.css'))
    .pipe(sourcemaps.write(''))
    .pipe(gulp.dest(path.output.style))
    // .pipe(reload({stream: true}));
});

gulp.task('styleRtl:build', function() {
    return gulp.src(path.input.styleRtl)
    .pipe(sourcemaps.init())
    .pipe(plumber({
        errorHandler: onError
    }))
    .pipe(sass())
    .pipe(autoprefixer({
        browsers: AUTOPREFIXER_BROWSERS,
        cascade: false
    }))
    .pipe(csso())
    .pipe(cleanCSS())
    .pipe(concat('style-rtl.min.css'))
    .pipe(sourcemaps.write(''))
    .pipe(gulp.dest(path.output.style))
    // .pipe(reload({stream: true}));
});

// IMG build
gulp.task('image:build', function () {
    gulp.src(path.input.img)
        .pipe(imagemin({
            progressive: true,
            svgoPlugins: [{removeViewBox: false}],
            use: [pngquant()],
            interlaced: true
        }))
        .pipe(gulp.dest(path.output.img));
});

// Font build
gulp.task('fonts:build', function() {
    gulp.src(path.input.fonts)
        .pipe(gulp.dest(path.output.fonts))
});

/* WATCH
=================================================================================*/
gulp.task('watch', function(){
    watch([path.watch.style], function(event, cb) {
        gulp.start('style:build');
    });
    watch([path.watch.styleRtl], function(event, cb) {
        gulp.start('styleRtl:build');
    });
    
    watch([path.watch.img], function(event, cb) {
        gulp.start('image:build');
    });
    watch([path.watch.fonts], function(event, cb) {
        gulp.start('fonts:build');
    });
});

gulp.task('clean', function() {
    return gulp.src('build').pipe(clean());
});

gulp.task('build', function(callback) {
  runSequence('style:build',
              'styleRtl:build',
              'image:build',
              'fonts:build',
              callback);
});

gulp.task('default', ['clean'], function() {
  gulp.start('build');
});