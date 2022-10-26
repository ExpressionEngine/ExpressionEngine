// package vars
const pkg = require('./package.json');

// gulp
const gulp = require("gulp");

// load all plugins in "devDependencies" into the variable $
const $ = require("gulp-load-plugins")({
    pattern: ['*'],
    replaceString: /\bgulp[\-.]/,
    scope: ["devDependencies"]
});

const DEVELOPMENT = !!($.yargs.argv.development);

// error logging
const onError = (err) => {
    console.log(err);
};

// Task runners
gulp.task('default', (cb) => {
    $.runSequence('build', 'watch', cb);
});

// Task builders
gulp.task('build', (cb) => {
    $.runSequence(['sass', 'scripts', 'vendors'], cb);
});

gulp.task('fronteditor', (cb) => {
    $.runSequence(['sass', 'scripts-front'], cb);
});

// TASKS
// ---------------------------------------------------------------------
// Copy fonts
gulp.task('copy-assets', () => {
    gulp.src(pkg.paths.src.fonts)
        .pipe(gulp.dest(pkg.paths.dist.fonts));
});

//Compile Sass into css and auto-inject into browsers
gulp.task('sass', () => {
    gulp.src(pkg.paths.src.sass)
        //filter out unchanged scss files, only works when watching
        .pipe($.if(global.isWatching, $.cached('sass')))
        //find files that depend on the files that have changed
        .pipe($.sassInheritance({dir: pkg.paths.src.sassIncludes}))
        //filter out internal imports (folders and files starting with "_" )
        .pipe($.filter(function (file) {
            return !/\/_/.test(file.path) || !/^_/.test(file.relative);
        }))
        .pipe($.sass({
            includePaths: pkg.paths.src.sassIncludes,
            outputStyle: 'expanded',
            errLogToConsole: true
        }).on('error', $.sass.logError))
        .pipe($.autoprefixer({ browsers: ['last 2 versions', 'Safari >= 8', 'ie >= 10'], flexbox: 'no-2009' }))
        .pipe($.csscomb())
        .pipe($.if(!DEVELOPMENT, $.cssnano({
            safe: true
        })))
        .pipe($.rename({suffix: '.min'}))
        .pipe(gulp.dest(pkg.paths.dist.css));
});

gulp.task('style-vendors', (done) => {
    gulp.src([

    ])
    .pipe($.concat('vendors.css'))
    .pipe($.if(!DEVELOPMENT, $.cssnano({
        safe: true
    })))
    .pipe($.rename({suffix: '.min'}))
    .pipe($.cached(pkg.paths.dist.js))
    .pipe(gulp.dest(pkg.paths.dist.js));
});

gulp.task('script-vendors', (done) => {
    gulp.src([
        './node_modules/van11y-accessible-modal-window-aria/dist/van11y-accessible-modal-window-aria.min.js'
    ])
    .pipe($.concat('vendors.js'))
    .pipe($.if(!DEVELOPMENT, $.uglify({output: {comments: 'some'}})).on('error', (e) => {
			console.log(e);
		}))
    .pipe($.rename({suffix: '.min'}))
    .pipe($.cached(pkg.paths.dist.js))
    .pipe(gulp.dest(pkg.paths.dist.js));
});

gulp.task('scripts', () => {
    gulp.src([
        pkg.paths.src.jsFolder + "main.js",
    ])
    .pipe($.babel({
        presets: [["@babel/preset-env", { modules: false }]]
    }))
    .pipe($.concat("main.js"))
    .pipe($.if(!DEVELOPMENT, $.uglify({output: {comments: 'some'}})).on('error', (e) => {
        console.log(e);
    }))
    .pipe($.rename({suffix: '.min'}))
    .pipe($.cached(pkg.paths.dist.js))
    .pipe(gulp.dest(pkg.paths.dist.js));
});

gulp.task('scripts-front', () => {
    gulp.src([
        pkg.paths.src.jsFolder + "fronteditor.js",
    ])
    .pipe($.babel({
        presets: [["@babel/preset-env", { modules: false }]]
    }))
    .pipe($.concat("fronteditor.js"))
    .pipe($.if(!DEVELOPMENT, $.uglify({output: {comments: 'some'}})).on('error', (e) => {
        console.log(e);
    }))
    .pipe($.rename({suffix: '.min'}))
    .pipe($.cached(pkg.paths.dist.js))
    .pipe(gulp.dest(pkg.paths.dist.js));
});



// Compress files
gulp.task('compress-br', function() {
    return gulp.src(pkg.paths.dist.base + '/**/*.{ttf,eot,woff,js,css,svg}', { base: "." })
    .pipe($.brotli.compress({
        extension: 'br',
        quality: 11
    }))
    .pipe(gulp.dest('./'));
});

gulp.task('compress-gz', function() {
    return gulp.src(pkg.paths.dist.base + '/**/*.{ttf,eot,woff,js,css,svg}', { base: "." })
    .pipe($.gzip())
    .pipe(gulp.dest('./'));
});

gulp.task('setWatch', () => {
    global.isWatching = true;
});

// Watch files and run tasks
gulp.task('watch', () => {
    gulp.watch(pkg.paths.src.sass, ['setWatch', 'sass']);
    gulp.watch(pkg.paths.src.js, ['scripts']);
});


gulp.task('vendors', () => {
    gulp.start('style-vendors', 'script-vendors');
});