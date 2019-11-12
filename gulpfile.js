const { src, dest, parallel, series } = require('gulp');
//const gulp = require('gulp');
const gulpZip = require('gulp-zip');
const del = require('del')
const es = require('event-stream');


async function cleanBuild() {
    await del('./build', {force: true})
}

async function clean() {
    await del('./dist', {force:true})
}

function copy(cb) {
    es.concat(
        src(['./**', '!node_modules', '!node_modules/**', '!admin/app/node_modules/**', '!debug.log', '!gulpfile.js', '!admin/app/src/**'])
            .pipe(dest('./dist/')),


    ).on('end', cb);
}

function zip() {
    return src('./dist/**')
        .pipe(gulpZip('critical-css.zip'))
        .pipe(dest('./build/'))
}

exports.clean = clean
exports.copy = copy
exports.zip = zip
exports.default = series(parallel(cleanBuild, clean), copy, zip, clean)