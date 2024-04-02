const gulp = require('gulp');
const concat = require('gulp-concat');

gulp.task('css:prod', () => {
    const postcss = require('gulp-postcss')
    const cleanCSS = require('gulp-clean-css');
    const cssimport = require("gulp-cssimport");
    const sourcemaps = require('gulp-sourcemaps')

    const sass = require('gulp-sass')(require('sass'));

    const files = [
        './public/css/moloni.materialize.scss',
        './public/css/style-datatables.scss',
        './public/css/style-company.scss',
        './public/css/style-login.scss',
        './public/css/style-logs.scss',
        './public/css/style.scss',
        './node_modules/datatables.net-dt/css/jquery.dataTables.css',
    ];

    return gulp.src(files)
        .pipe(sourcemaps.init())
        .pipe(sass({includePaths: ['node_modules']}))
        .pipe(cssimport())
        .pipe(postcss([
            require('autoprefixer'),
            require('postcss-combine-media-query')
        ]))
        .pipe(cleanCSS({level: {1: {specialComments: 0}}}))
        .pipe(concat("compiled.min.css"))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest("public/"))
});

gulp.task('js:prod', () => {
    const babel = require("gulp-babel");
    const plumber = require("gulp-plumber");
    const uglify = require('gulp-uglify');

    const files = [
        './node_modules/materialize-css/dist/js/materialize.js',
        './node_modules/datatables.net/js/jquery.dataTables.js',
        './public/js/Logs.js',
    ];

    return (
        gulp.src(files)
            .pipe(plumber())
            .pipe(babel({
                presets: [
                    ["@babel/env", {modules: false}],
                ]
            }))
            .pipe(concat("compiled.js"))
            .pipe(gulp.dest("public/"))
            .pipe(uglify())
            .pipe(concat("compiled.min.js"))
            .pipe(gulp.dest("public/"))
    )
});
