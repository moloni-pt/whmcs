const gulp = require('gulp');
const postcss = require('gulp-postcss')
const purgecss = require("gulp-purgecss");

gulp.task('css:prod', () => {
    const cleanCSS = require('gulp-clean-css');

    return gulp.src('./*/*.css')
        .pipe(postcss([
            require('precss'),
            require('autoprefixer')({grid: 'autoplace'}),
            require('postcss-combine-media-query')
        ]))
        .pipe(purgecss({
            content: ['*.php', '*.html', 'assets/*.js']
        }))
        .pipe(cleanCSS({level: 1}))
        .pipe(gulp.dest('public/css/'));
});
