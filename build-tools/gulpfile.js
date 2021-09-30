/* jslint node: true */
'use strict';

var properties = require('./build.json')

// Get optional custom version
for (var i = 0; i < process.argv.length; i++) {
	var arg = process.argv[i];
	if (arg.indexOf('--version=') != -1) {
		properties.version = arg.replace('--version=', '');
    }

    if (arg.indexOf('--tag=') != -1) {
        properties.tag = arg.replace('--tag=', '');
    }
}

var	gulp = require('gulp'),
	JSZip = require('jszip'),
	dateformat = require('dateformat'),
	del = require('del'),
	exec = require('child_process').exec,
	fs = require('fs'),
	mkdirp = require('mkdirp'),
	phplint = require('phplint').lint,
	plugin = require('gulp-load-plugins')(),
	runSequence = require('run-sequence'),
	Q = require('q');

if (fs.existsSync('./local_config.json')) {
	var localProps = require('./local_config.json')
	properties = Object.assign(properties, localProps)
}

properties.local_repositories.app = 'C:/OSPanel/domains/ExpressionEngine-Private/'
if (process.env.APP_REPO_PATH) {
    properties.local_repositories.app = process.env.APP_REPO_PATH;
}

properties.local_repositories.pro = 'C:/OSPanel/domains/ExpressionEngine-Pro/'
if (process.env.PRO_REPO_PATH) {
    properties.local_repositories.pro = process.env.PRO_REPO_PATH;
}

if (process.env.DOCS_REPO_PATH) {
    properties.local_repositories.docs = process.env.DOCS_REPO_PATH;
}

if (typeof properties.version == 'undefined') {
	properties.version = properties.tag;
	if (properties.version.substr(0, 1) === 'v') {
		properties.version = properties.version.substr(1);
	}
}

var paths = {
	'builds': __dirname + '/builds/',
	'docs': __dirname + '/builds/EEDocs' + properties.version + '/',
	'app': __dirname + '/builds/ExpressionEngine' + properties.version + '/'
};

gulp.task('default', ['docs', 'app'], function () {
	exec('open ' + paths.builds, function (err) {
		if (err) exec('xdg-open' + paths.builds);
	});
});

/**
 * Build the main application
 */
gulp.task('app', ['_preflight'], function (cb) {
	// Kick off the build
	var filesToDelete = [
		'build/',
		'tests/',
		'build-tools/',
		
		'images/*/*',
		'images/about',
		'!images/*/index.html',
		'!images/smileys/*',
		'!images/avatars/*',
	];
	console.log(paths.app, filesToDelete)

	deleteFiles(paths.app, filesToDelete)
		.then(() => createHashManifest(paths.app))
		.then(() => compressPackage(paths.app))
		.then(() => getBuildSignature(paths.app, 'Core'))
		.then(signature => {
			if (process.argv.indexOf('--upload-circle-build') > -1) {
				var version = properties.dp ? properties.tag : properties.version,
					zip_file = paths.app.replace(/\/$/, '.zip');

				return uploadBuild(zip_file, version, signature)
			}
			return Promise.resolve()
		})
		.then(() => cb())
		.catch(error => setTimeout(() => { throw error }))
});

/**
 * Build the main application
 * but do not zip or send or anything
 */
 gulp.task('build-app', ['_preflight'], function (cb) {
	// Kick off the build
	var filesToDelete = [
		'build/'
	];

	deleteFiles(paths.app, filesToDelete)
		.then(() => createHashManifest(paths.app))
		.then(() => cb())
		.catch(error => setTimeout(() => { throw error }))
});

/**
 * Run some preflight tasks:
 * - setup properties
 * - clone the application repo
 * - check to see if the update exists
 * - bump the version
 * - run php -l
 */
gulp.task('_preflight', ['_properties'], function (cb) {
	var clone_or_archive = properties.use_local ? '_archive_app' : '_clone_app';

	console.log('Mode:', (properties.use_local ? 'Archive' : 'Clone'));

	runSequence(
		clone_or_archive,
		'_archive_pro',
		'_version_bump',
		['_update_exists', '_set_debug', '_replace_jira_collector', '_boot_hack', '_wizard_hack', '_create_config', '_dp_config', '_dp_license', '_fill_updater_dependencies'],
		['_phplint', '_compress_js'],
		'_delete_files',
		cb
	);
});

/**
 * Clone the application into the build directory
 */
gulp.task('_clone_app', ['_build_directories'], function (cb) {
	clone_repo('app', cb);
});

/**
 * Archive the application and build from that instead of pulling files from the server
 */
gulp.task('_archive_app', ['_build_directories'], function (cb) {
	archive_repo('app', cb);
});

gulp.task('_archive_pro', function (cb) {
	archive_repo('pro', cb);
});

/**
 * Just copy over application folder
 */
 gulp.task('_copy_app', ['_build_directories'], function (cb) {
	//copy_app(cb)
	mkdirp.sync(paths.builds + 'ExpressionEngine' + properties.version);
	console.log(paths.app);

	var files = [
		properties.local_repositories.app + 'images/**/*', 
		properties.local_repositories.app + 'system/**/*', 
		properties.local_repositories.app + 'themes/**/*', 
		properties.local_repositories.app + 'admin.php', 
		properties.local_repositories.app + 'index.php', 
		properties.local_repositories.app + 'favicon.ico', 
		properties.local_repositories.app + 'LICENSE.txt'
	];

	for (var i = 0; i < files.length; i++) {
		gulp.src(files[i], { base: properties.local_repositories.app })
			.pipe(gulp.dest(paths.app));
	}

	//cb();
});

/**
 * Set $debug = 0; in all index.php files
 */
gulp.task('_set_debug', function (cb) {
	gulp.src(paths.app + 'index.php')
		.pipe(plugin.replace(
			/^(    |\t)\$debug = 1;/m,
			'    $debug = 0;'
		))
		.pipe(gulp.dest(paths.app));

	gulp.src(paths.app + 'system/index.php')
		.pipe(plugin.replace(
			/^(    |\t)\$debug = 1;/m,
			'    $debug = 0;'
		))
		.pipe(gulp.dest(paths.app + 'system'));

	gulp.src(paths.app + 'admin.php')
		.pipe(plugin.replace(
			/^(    |\t)\$debug = 1;/m,
			'    $debug = 0;'
		))
		.pipe(gulp.dest(paths.app));

	cb();
});

/**
 * Replace JIRA collector tag
 */
gulp.task('_replace_jira_collector', function (cb) {
	var jiraCode = '';
	if (process.argv.indexOf('--jira-collector') > -1) {
		jiraCode = '<script type="text/javascript" src="https://packettide.atlassian.net/s/d41d8cd98f00b204e9800998ecf8427e-T/-e6zu8v/b/23/a44af77267a987a660377e5c46e0fb64/_/download/batch/com.atlassian.jira.collector.plugin.jira-issue-collector-plugin:issuecollector/com.atlassian.jira.collector.plugin.jira-issue-collector-plugin:issuecollector.js?locale=en-US&collectorId=3804d578"></script>';
	}

	var files = [
		paths.app + 'system/ee/ExpressionEngine/View/_shared/footer.php',
		paths.app + 'system/ee/legacy/errors/error_exception.php',
		paths.app + 'system/ee/legacy/errors/error_general.php'
	];
	
	files.forEach(function(item, index) {
		var dest = item.substring(0, item.lastIndexOf('/'));
		gulp.src(item)
			.pipe(plugin.replace(
				'<!-- <JIRA Collector> -->',
				jiraCode
			))
			.pipe(gulp.dest(dest));
	});

	cb();
});

/**
 * Change PATH_JS constant to compressed for non-DP builds
 */
gulp.task('_dp_config', function (cb) {
	//if (properties.dp !== true) {
		/*return */gulp.src(paths.app + 'system/ee/legacy/libraries/Core.php')
			.pipe(plugin.replace(
				/define\('PATH_JS',(\s+)'.*?'\);/gi,
				"define('PATH_JS',$1'" + 'compressed' + "');"
			))
			.pipe(gulp.dest(paths.app + 'system/ee/legacy/libraries/'));
	//}
	cb();
});

/**
 * Add developer preview license for DP and RC builds
 */
gulp.task('_dp_license', function (cb) {
	//if (properties.dp || properties.rc) {
		gulp.src('./license.dp.key')
			.pipe(gulp.dest(paths.app + 'system/user/config/license.key'))
	//}
	cb()
})

/**
 * Remove the FALSE && from the installer
 */
gulp.task('_boot_hack', function () {
	return gulp.src(paths.app + 'system/ee/' + properties.namespaced_path + '/Boot/boot.php')
		.pipe(plugin.replace(
			/if \(FALSE \&\& /g,
			"if ("
		))
		.pipe(gulp.dest(paths.app + 'system/ee/' + properties.namespaced_path + '/Boot/'));
});

/**
 * Update the wizard.php file removing various hacks
 */
gulp.task('_wizard_hack', function () {
	return gulp.src(paths.app + 'system/ee/installer/controllers/wizard.php')
		.pipe(plugin.replace("if (TRUE ||", "if ("))
		.pipe(plugin.replace(
			/\/\/\s*BUILD_REMOVE_CJS_START[\s\S]*BUILD_REMOVE_CJS_END/g,
			''
		))
		.pipe(gulp.dest(paths.app + 'system/ee/installer/controllers/'));
});

/**
 * Create the config.php file
 */
gulp.task('_create_config', function (cb) {
	fs.open(paths.app + 'system/user/config/config.php', 'w', '0666', function (err) {
		if (err) throw err;
		cb();
	});
});

/**
 * Delete files we don't need from the repository
 */
gulp.task('_delete_files', function (cb) {
	var filesToDelete = [
		'**/.DS_Store',
		'.atom-build.yml',
		'.babelrc',
		'.circleci',
		'.editorconfig',
		'.env.php',
		'.git',
		'.github',
		'.gitignore',
		'.languagebabel',
		'.mailmap',
		'.php_cs.dist',
		'brunch-config.js',
		'AUTHORS.md',
		'build.json',
		'phpcs.ruleset.xml',
		'changelogs/',
		'CONTRIBUTING.md',
		'docker-compose.yml',
		'Dockerfile',
		'eetools',
		'gulpfile.js',
		'licenses',
		'package-lock.json',
		'package.json',
		'README.md',
		'scripts/',
		'system/ee/legacy/libraries/Ldap.php',

		'system/user/*/*',
		'!system/user/*/index.html',
		'!system/user/*/.htaccess',
		'!system/user/config/config.php',

		'cp-styles',

		'themes/ee/member/*',
		'themes/ee/site/*',
		'themes/ee/wiki/*',
		'!themes/ee/*/index.html',
		'!themes/ee/*/default',
		'!themes/ee/wiki/azure',

		'system/ee/EllisLab/Tests/',
		'system/ee/ExpressionEngine/Tests/',

		'src',
		'vue.config.js',
		'npm-shrinkwrap.json',
		'jest.config.js',
		'babel.config.js',
		'system/ee/ExpressionEngine/Addons/pro/composer.json',
		'system/ee/ExpressionEngine/Addons/pro/composer.lock',
		'system/ee/ExpressionEngine/Addons/pro/Composer.php',
	];

	//if (properties.dp !== true) {
		filesToDelete.push(
			'themes/ee/asset/javascript/src/'
		);
	//}
	if (properties.dp || properties.rc) {
		filesToDelete.push(
			'!system/user/config/license.key'
		);
	}

	// Delete files
	del(filesToDelete, {cwd: paths.app}, cb);
});

/**
 * Ensure that the update file (e.g. ud_x_x_x.php) exists for the current
 * version
 */
gulp.task('_update_exists', function () {
	updateExists(paths.app)
});

/**
 * Run php -l on all of the PHP files, ignore specific files known to cause
 * problems
 */
gulp.task('_phplint', function (cb) {
	if (process.argv.indexOf('--skip-lint') > -1) return cb();

	phplint([paths.app + '/**/*.php', '!' + paths.app + '/**/config_tmpl.php'], function (err, stdout, stderr) {
		if (err) {
			cb(err);
			process.exit(1);
		}
		cb();
	});
});

/**
 * Bump the version numbers
 */
gulp.task('_version_bump', ['_properties'], function () {
	return versionBump(paths.app);
});

/**
 * Compress the javascript before the build
 */
gulp.task('_compress_js', function () {
	gulp.src(paths.app + 'themes/ee/asset/javascript/src/index.html')
		.pipe(gulp.dest(paths.app + 'themes/ee/asset/javascript/compressed/'));

	var compressed = compressJs(paths.app);
	console.log('Compress: DONE');
	return compressed;
});

/**
 * Build the documentation, zip the docs, delete the build directory
 */
gulp.task('docs', ['_properties', '_clone_docs'], function (cb) {
	// Start the build
	exec('npm install && npm run build', {cwd: paths.docs, quiet: true}, function () {
		const path = paths.docs + 'build/'

		compressPackage(path)
			.then(() => {
				fs.renameSync(
					paths.docs + 'build.zip',
					paths.builds + "EEDocs" + properties.version + '.zip'
				)
				del.sync(paths.docs, {force: true})
			})
			.then(() => cb())
			.catch(error => setTimeout(() => { throw error }))
	});
});

/**
 * Clone the docs from their git repository
 */
gulp.task('_clone_docs', ['_build_directories'], function (cb) {
	var clone_or_archive = properties.use_local ? archive_repo : clone_repo;

	// console.log('MODE:', clone_or_archive);

	clone_or_archive('docs', function () {
		// Update the config version number just in case
		gulp.src(paths.docs + 'scripts/config.js')
			.pipe(plugin.replace(
				/currentVersion: '.*',/,
				"currentVersion: '" + properties.version + "',"
			)).pipe(gulp.dest(paths.docs + 'scripts/'))
			.on('end', cb)
	});
});

gulp.task('version_bump', ['_properties'], function () {
	updateExists();
	return versionBump();
});

gulp.task('compress_js', function () {
	return compressJs();
});

gulp.task('_fill_updater_dependencies', function () {
	return fillUpdaterDependencies(paths.app);
});

gulp.task('fill_updater_dependencies', function () {
	return fillUpdaterDependencies();
});

/**
 * Generate certain properties (build date, update file, DP status)
 *
 * Given a tag with an identifier (3.1.0-dp.4), we can automatically gather the
 * version (3.1.0) and the identifier (dp.4), however both can be supplied
 * manually.
 *
 * @param {String} tag The tag to pull from the repository, must be pushed.
 * @param {String} version (Optional) The version to use when replacing version
 *                         numbers in the code. If no verison is defined, we use
 *                         the contents of the tag up to (but excluding) a `-`.
 *                         If no `-` exists, we use the whole version.
 * @param {Number} build The build date as a number in the `yyyymmdd` format
 *                       (e.g. 20151210)
 * @param {String} identifier (Optional) The version identifier (e.g. dp.2,
 *                            beta.1). If no identifier is defined, we use the
 *                            contents of tag starting from the `-` (excluding
 *                            it) to the end of the tag.
 * @param {Object} repositories Object containing the URLs of the app and
 *                              documentation repositories
 */
gulp.task('_properties', function () {
	properties.use_local = process.argv.indexOf('--local') > -1;

	// Define identifier
	if (typeof properties.identifier == 'undefined') {
		if (properties.tag.lastIndexOf('-') >= 0) {
			properties.identifier = properties.tag.substr(properties.tag.lastIndexOf('-') + 1);
		} else {
			properties.identifier = '';
		}
	}

	// Set build date to today (e.g. 20150706)
	if (typeof properties.build === 'undefined') {
		properties.build = dateformat(new Date(), 'yyyymmdd');
	}

	// Generate ud_n_n_n.php build version
	var normalizedVersion = properties.version;
	if (properties.version.lastIndexOf('-') >= 0) {
		// Replace the hyphen in the identifier with a dot so we can build the update file name properly.
		normalizedVersion = properties.version.replace('-', '.');
	}

	var segments = normalizedVersion.split('.');
	segments.forEach(function(segment, index) {
		if ((index == 1 || index == 2) && segment.length == 1) {
			segments[index] = '0' + segment;
		}
	});
	properties.update_file = 'ud_' + segments.join('_') + '.php';
	properties.major_version = segments[0];

	properties.namespaced_path = 'ExpressionEngine';

	if (properties.major_version < 6) {
		properties.namespaced_path = 'EllisLab/ExpressionEngine';
	}

	console.log('Major Version:', properties.major_version);
	console.log('Namespaced Path:', properties.namespaced_path);

	// Determine DP status
	properties.dp = (properties.version.match(/\-dp/)) ? true : false;

	// Release candidate?
	properties.rc = false;//(properties.version.match(/\-rc/)) ? true : false;
});

/**
 * Create build directories
 */
gulp.task('_build_directories', function () {
    console.log('Making Builds Path', paths.builds);
    mkdirp.sync(paths.builds);
});

/**
 * Clone a repository
 * @param  {string}   type The "type" of repository, either app or docs
 * @param  {Function} cb   Callback to call after cloneing
 * @return {void}
 */
var clone_repo = function (type, cb) {
	del.sync(paths[type], {force: true});
	plugin.git.clone(properties.repositories[type], {
		"args": "--depth 1 --branch " + properties.tag + " " + paths[type],
		"quiet": true
	}, function (err) {
		if (err) throw err;
		cb();
	});
};

/**
 * Archive a repository and build based on that, instead of pulling from the server
 * @param  {string}   type The "type" of repository, either app or docs, only 'app' is supported at the moment
 * @param  {Function} cb   Callback to call after cloneing
 * @return {void}
 */
var archive_repo = function(type, cb) {
	console.log('ARCHIVE_REPO');
	console.log('Path Type:', type);
	if (type=='pro') {
		paths[type] = paths['app']
	} else {
		console.log('Deleting:', paths[type]);

		del.sync(paths[type], {force: true});
	}
	var head = process.argv.indexOf('--head') > -1;
	var dirty = process.argv.indexOf('--dirty') > -1;
	var non_tag_reference = dirty ? '$(git stash create)' : 'HEAD';
	var reference = head || dirty ? non_tag_reference : properties.tag;
	var zip_file = ((type == 'docs') ? 'EEDocs' : 'ExpressionEngine') + properties.version + type + '.zip';
	var path = properties.local_repositories[type];

	console.log('Head:', head);
	console.log('Dirty:', dirty);
	console.log('Non-Tag Reference:', non_tag_reference);
	console.log('Reference:', reference);
	console.log('Using Path:', path);
	console.log('Tag:', properties.tag);
	console.log('Version:', properties.version);
	console.log('Zip:', zip_file);
	console.log('--------------------------------------------');
	console.log('Running: git archive -o ' + zip_file + ' ' + reference);
	console.log('--------------------------------------------');

	exec('git archive -o ' + zip_file + ' ' + reference, { cwd: path }, (err, stdout, stderr) => {
		if (err) throw err;
		console.log('archived ' + zip_file);
		console.log('cwd: ' + path);
		console.log('unzip -o ' + zip_file + ' -d ' + paths[type]);

        exec('unzip -o ' + zip_file + ' -d ' + paths[type], { cwd: path, maxBuffer: 2000 * 1024 }, (err, stdout, stderr) => { // cwd: path, maxBuffer: 500 * 1024
			if (err) throw err;

			console.log('deleting zip file');
			del(zip_file, { cwd: path }, cb);
		});
	});
}

/**
 * Compress the final package, deleting specified files and the build directory
 *
 * @param  {string} path The path of the build directory
 * @return {Promise}
 */
var compressPackage = function (path) {
	return new Promise((resolve, reject) => {
		del(path.replace(/\/$/, '.zip'), {cwd: path, force: true}, function (err) {
			if (err) return reject(err);
			// Zip everything up
			exec(
				'zip -r ' + path.replace(/\/$/, '.zip') + ' .',
                { cwd: path, maxBuffer: 1000 * 1024}, // cwd: path, maxBuffer: 500 * 1024
				function (err, stdout, stderr) {
					if (err) return reject(err);

					// Delete build directory
					del('.', {cwd: path, force: true}, resolve);
				}
			);
		});
	})
};

/**
 * Deletes files
 *
 * @param  {string}       path          The path of the build directory
 * @param  {string|array} filesToDelete Either a file path or an array of file paths
 *
 * @return {Promise}
 */
var deleteFiles = function(path, filesToDelete) {
	return new Promise((resolve, reject) => {
		del(filesToDelete, {cwd: path, force: true}, resolve)
	})
}

/**
 * Bump the version numbers
 *
 * @param  {string} path The path to change the version in
 *
 * @return {void}
 */
 var versionBump = function (path) {
	path = (typeof path !== 'undefined') ? path : properties.local_repositories.app + '/';

	var fns = [
		function() {
			var file = path + 'system/ee/legacy/libraries/Core.php';

			fs.open(file, 'r', function (err, fd) {
				if (err) throw err;
			});

			return gulp.src(file)
				.pipe(plugin.replace(
					/define\('APP_VER',(\s+)'.*?'\);/gi,
					"define('APP_VER',$1'" + properties.version + "');"
				))
				.pipe(plugin.replace(
					/define\('APP_BUILD',(\s+)'.*?'\);/g,
					"define('APP_BUILD',$1'" + properties.build + "');"
				))
				.pipe(plugin.replace(
					/define\('APP_VER_ID',(\s+)'.*?'\);/g,
					"define('APP_VER_ID',$1'" + properties.identifier + "');"
				))
				.pipe(gulp.dest('system/ee/legacy/libraries/', {cwd: path}));
		},
		/*function () {
			var file = path + '/tests/cypress/support/config/config.php';

			fs.open(file, 'r', function (err, fd) {
				if (err) throw err;
			});

			return gulp.src(file)
				.pipe(plugin.replace(
					/\$config\['app_version'\].*$/gim,
					"$config['app_version'] = '" + properties.version + "';"
				))
				.pipe(gulp.dest('tests/cypress/support/config/', { cwd: path }));
		},*/
		function() {
			var file = path + '/system/ee/installer/controllers/wizard.php';

			fs.open(file, 'r', function (err, fd) {
				if (err) throw err;
			});

			return gulp.src(file)
				.pipe(plugin.replace(
					/\$version(\s+)= '.*?';/gi,
					"\$version$1= '" + properties.version + "';"
				))
				.pipe(gulp.dest('system/ee/installer/controllers/', {cwd: path}));
		}
	];

	var promises = fns.map(function(fn) {
		var deferred = Q.defer();

		fn().on('end', function () {
			deferred.resolve();
		});

		return deferred.promise;
	});

	return Q.all(promises);
 };

/**
 * Checks if the update file exists
 *
 * @param  {string} path The path to change the version in
 *
 * @return {void}
 */
 var updateExists = function (path) {
 	path = (typeof path !== 'undefined') ? path : properties.local_repositories.app;

	// Don't bother if we're just building from an arbitrary branch/tag
	if ( ! properties.tag.match(/\d+\.\d+\.\d+/)) {
		return;
	}

 	fs.open(path + '/system/ee/installer/updates/' + properties.update_file, 'r', function (err, fd) {
 		if (err) throw err;
 	});
 }

/**
 * Compress the javascript
 *
 * @param  {string} path The path to change the version in
 *
 * @return {void}
 */
var compressJs = function (path) {
	path = (typeof path !== 'undefined') ? path : './';

	console.log('Compress Path:', path + 'themes/ee/asset/javascript/src/**/*.js');
	// process.exit();
	return gulp.src([path + 'themes/ee/asset/javascript/src/**/*.js', path + 'themes/ee/asset/javascript/src/**/redactor.min.css', '!' + path + 'themes/ee/asset/javascript/src/fields/rte/redactor/plugins/**/*', '!' + path + 'themes/ee/asset/javascript/src/**/redactor.js'])
		//.pipe(plugin.uglify({preserveComments: 'some'}))
		.pipe(gulp.dest(path + 'themes/ee/asset/javascript/compressed/'));
};

/**
 * Creates a manifest of all the files in the package and their respective SHA1 hashes
 *
 * @param  {string} path The path to create a manifest for
 *
 * @return {Promise}
 */
var createHashManifest = function (path) {
	return new Promise((resolve, reject) => {
		path = (typeof path !== 'undefined') ? path : '.';

		var hashMainfestFile = 'hash-manifest',
			hashsum = require('gulp-hashsum');

		// Create the manifest with hashsum; we create it in the root because
		// the paths in the manifest file are relative to where it's created
		gulp.src(path + '/**')
			.pipe(hashsum({
				dest: path,
				delimiter: ' ',
				filename: hashMainfestFile,
				hash: 'sha384',
				json: true
			}))
			.on('end', function() {
				// Move to updater folder
				gulp.src(path + '/' + hashMainfestFile)
					.pipe(gulp.dest(path + '/system/ee/installer/updater/'))
					.on('end', function() {
						// Delete manifest from root
						del(hashMainfestFile, { cwd: path }, resolve);
					});
			});
	})
};

/**
 * The updater microapp requires some dependencies normally kept and
 * maintained in the main ExpressionEngine app. This copies over those
 * dependencies to the micro app for use and also alters their namespaces
 * to be prefixed with ExpressionEngine\Updater
 *
 * @param  {string} path The root math of the EE folder to perform the copy
 *
 * @return {void}
 */
var fillUpdaterDependencies = function (path) {
	path = (typeof path !== 'undefined') ? path : './';

	var filesToCopy = [
		properties.namespaced_path + '/Boot/boot.common.php',
		properties.namespaced_path + '/Core/Autoloader.php',
		properties.namespaced_path + '/Library/Filesystem/Filesystem.php',
		properties.namespaced_path + '/Library/Filesystem/FilesystemException.php',
		properties.namespaced_path + '/Service/Logger/File.php',
		properties.namespaced_path + '/Service/Updater/Logger.php',
		properties.namespaced_path + '/Service/Updater/SteppableTrait.php',
		properties.namespaced_path + '/Service/Updater/UpdaterException.php',
		properties.namespaced_path + '/Service/Updater/Verifier.php'
	];

	filesToCopy.forEach(function(item, index) {
		// Get parent path of file
		var dest = item.substring(0, item.lastIndexOf('/'));

		gulp.src(path + 'system/ee/' + item)
			.pipe(plugin.replace(
				/(namespace|use) ExpressionEngine\\/gi,
				'$1 ExpressionEngine\\Updater\\'
			))
			.pipe(plugin.replace(
				/(namespace|use) EllisLab\\ExpressionEngine\\/gi,
				'$1 EllisLab\\ExpressionEngine\\Updater\\'
			))
			.pipe(gulp.dest(path + 'system/ee/installer/updater/' + dest.replace(properties.namespaced_path + '/', properties.namespaced_path + '/Updater/'), { overwrite: true }));
	});
};

/**
 * Shells in to our signing server to generate a signature for the builds.
 * Requires that your public key is placed on the server.
 *
 * @param  {string} path    Path of build we want to make a signature for, will infer zip file name
 * @param  {string} version Identification string to show in log, typically "Pro" or "Core"
 *
 * @return {Promise}
 */
var getBuildSignature = function(path, version) {
	return new Promise((resolve, reject) => {
		var zip_file = path.replace(/\/$/, '.zip');

		exec('shasum -a 384 ' + zip_file,
			{ cwd: paths.builds },
			function(err, hash, stderr) {
				if (err) return reject(err);

				var hash = hash.substring(0, hash.indexOf(' '));
				var cmd = `php sign.php "${hash}"`;

				//console.log('Hash:', hash);
				//console.log('Command:', cmd);

				exec(cmd, { cwd: '.' },
					function(err, signature, stderr) {
						if (err) return reject(err);

                        console.log("Signature for ExpressionEngine "+version+":\n\n"+signature+"\n");
                        fs.writeFile(paths.builds + '/signature.txt', signature, function (err) {
                            if (err) return console.log(err);
                        });
						resolve(signature);
					}
				);
			}
		);
	})
};
