const { src, dest, series, watch } = require('gulp')

const ts = require('gulp-typescript')
const babel = require('gulp-babel')
const del = require('del')
const concat = require('gulp-concat')
const uglify = require('gulp-uglify')
const rename = require('gulp-rename')
const cleanCSS = require('gulp-clean-css');
const sourcemaps = require('gulp-sourcemaps');

const jsSourceFolder = 'themes/ee/cp/js/src/'
const jsVendorFolder = 'themes/ee/cp/js/src/vendor/'
const jsBuildFolder = 'themes/ee/cp/js/build/'
const rteRedactorFolder = 'themes/ee/asset/javascript/src/fields/rte/redactor/';

function cleanJs() {
	return del([jsBuildFolder + '**/*'])
}

function buildTypeScript() {
	return src([jsSourceFolder + '**/*.ts', jsSourceFolder + '**/*.tsx', '!' + jsVendorFolder + '**/*'])
        .pipe(ts({
			noImplicitAny: false,
			jsx: "react",
			target: "es5",
			isolatedModules: true
        }))
        .pipe(dest(jsBuildFolder))
}

function buildJavascript() {
	return src([jsSourceFolder + '/**/*.js', jsSourceFolder + '/**/*.jsx', '!' + jsVendorFolder + '**/*'])
		.pipe(babel({
			presets: [
				'@babel/env',
				'@babel/preset-react'
			],
			plugins: [
				'@babel/plugin-proposal-class-properties'
			]
		}))
		.pipe(dest(jsBuildFolder))
}

function cleanRte() {
	return del(rteRedactorFolder + '*.min.*')
}

function buildRteRedactorCss() {
	return src(rteRedactorFolder + 'redactor.css')
		.pipe(cleanCSS())
		.pipe(rename('redactor.min.css'))
		.pipe(dest(rteRedactorFolder));
}

function buildRteRedactorJs() {
	return src([rteRedactorFolder + 'redactor.js', rteRedactorFolder + 'plugins/**/*.js'])
		.pipe(sourcemaps.init())	
		.pipe(concat('redactor.min.js'))
		.pipe(uglify())
		.pipe(sourcemaps.write('./'))
		.pipe(dest(rteRedactorFolder));
}

function buildVendorJs() {
	// Vendor files are not compiled, just moved
	return src(jsVendorFolder + '**/*.js')
		.pipe(dest(jsBuildFolder + 'vendor/'))
}

const buildRte = series(cleanRte, buildRteRedactorCss, buildRteRedactorJs)

const buildAllJs = series(cleanJs, buildRte, buildTypeScript, buildJavascript, buildVendorJs)

function watchJs() {
	watch([jsSourceFolder, jsVendorFolder, rteRedactorFolder], buildAllJs)
}

exports.rte = buildRte
exports.buildJs = buildAllJs
exports.watchJs = watchJs
