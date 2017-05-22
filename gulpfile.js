"use strict"

var gulp = require('gulp');
var h = require('gulp-helpers');
var publish = h.publish('Biigle\\Modules\\Geo\\GeoServiceProvider', 'public');

h.paths.sass = 'src/resources/assets/sass/';
h.paths.js = 'src/resources/assets/js/';
h.paths.public = 'src/public/assets/';

gulp.task('sass-main', function () {
    h.sass('main.scss', 'main.css');
});

gulp.task('sass', ['sass-main']);

gulp.task('js-main', function (cb) {
    h.js('geo/**/*.js', 'main.js', cb);
});

gulp.task('js-volumes', function (cb) {
    h.js('volumes/**/*.js', 'volumes.js', cb);
});

gulp.task('js', ['js-main', 'js-volumes']);

gulp.task('watch', function () {
    gulp.watch(h.paths.sass + '**/*.scss', ['sass']);
    gulp.watch(h.paths.js + 'geo/**/*.js', ['js-main']);
    gulp.watch(h.paths.js + 'volumes/**/*.js', ['js-volumes']);
    gulp.watch(h.paths.public + '**/*', publish);
});

gulp.task('default', ['sass', 'js'], publish)
