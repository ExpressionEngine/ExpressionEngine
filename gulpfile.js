const { src, dest, series, watch } = require('gulp')

const ts = require('gulp-typescript')
const babel = require('gulp-babel')
const del = require('del')

const jsSourceFolder = 'themes/ee/cp/js/src/'
const jsVendorFolder = 'themes/ee/cp/js/src/vendor/'
const jsBuildFolder = 'themes/ee/cp/js/build/'

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

function buildVendorJs() {
	// Vendor files are not compiled, just moved
	return src(jsVendorFolder + '**/*.js')
		.pipe(dest(jsBuildFolder + 'vendor/'))
}


const buildAllJs = series(cleanJs, buildTypeScript, buildJavascript, buildVendorJs)

function watchJs() {
	watch([jsSourceFolder, jsVendorFolder], buildAllJs)
}


exports.buildJs = buildAllJs
exports.watchJs = watchJs
