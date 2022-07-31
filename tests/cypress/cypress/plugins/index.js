// ***********************************************************
// This example plugins/index.js can be used to load plugins
//
// You can change the location of this file or turn off loading
// the plugins file with the 'pluginsFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/plugins-guide
// ***********************************************************

// This function is called when a project is opened or re-opened (e.g. due to
// the project's config changing)

module.exports = (on, config) => {
    // `on` is used to hook into various events Cypress emits
    // `config` is the resolved Cypress config
    const Database = require('./database.js');
    const db = new Database({
        host: config.env.DB_HOST,
        port: config.env.DB_PORT,
        user: config.env.DB_USER,
        password: config.env.DB_PASSWORD,
        database: config.env.DB_DATABASE
    });

    const db_defaults = {
        database: config.env.DB_DATABASE,
        dbdriver: 'mysqli',
        hostname: config.env.DB_HOST,
        password: config.env.DB_PASSWORD,
        username: config.env.DB_USER
    }

    const Filesystem = require('./filesystem.js');
    const fs = new Filesystem;

    const Installer = require('./installer.js');
    const installer = new Installer;

    const Updater = require('./updater.js');
    const updater = new Updater;

    const baseUrl = config.env.CYPRESS_BASE_URL || null;
    if (baseUrl) {
        config.baseUrl = baseUrl;
    }

    const { lighthouse, prepareAudit } = require('cypress-audit');

    const child_process = require('child_process');

    const consoleLog = require('cypress-log-to-output');
    consoleLog.install(on, (type, event) => {
        if (event.level === 'error' || event.type === 'error') {
          return true
        }
        return false
      }, { recordLogs: true });

    on('task', {
        'console:getLogs': () => {
            return consoleLog.getLogs()
        }
    })

    /*on('after:spec', (spec, results) => {
        var filename = spec.name.split('/');
        fs.create('cypress/downloads');
        fs.createFile('cypress/downloads/' + filename[1].split('.')[0] + '.console.log', consoleLog.getLogs().join("\r\n"));
    })*/

    on('task', {
        'db:clear': () => {
            return db.truncate()
        }
    })

    on('task', {
        'db:seed': () => {
            var tempSeed = 'seed.sql';
            fs.delete('../../system/user/cache/default_site/');

            if(fs.exists(db.sqlPath(tempSeed))) {
                return db.seed(tempSeed);
            }

            var renameInstaller = false;
            if (!fs.exists('../../system/ee/installer')) {
                renameInstaller = true;
                fs.rename('../../system/ee/_installer', '../../system/ee/installer');
            }
            return db.truncate().then(()=>{
                var properties = JSON.parse(fs.read('../../build-tools/build.json'))
                let command = `cd support/fixtures && php initDb.php --version ${properties.tag} --url ${config.baseUrl} --username ${config.env.USER_EMAIL} --password ${config.env.USER_PASSWORD} --db_host ${config.env.DB_HOST} --db_user ${config.env.DB_USER} --db_database ${config.env.DB_DATABASE} --db_password ${config.env.DB_PASSWORD}`;

                //console.log(command);
                try {
                    var a = child_process.execSync(command).toString();
                    //console.log(a);
                } catch (error) {
                    console.log('------')
                    console.log(error.status);  // 0 : successful exit, but here in exception it has to be greater than 0
                    console.log(error.message); // Holds the message you typically want.
                    console.log(error.stderr.toString());  // Holds the stderr output. Use `.toString()`.
                    console.log(error.stdout.toString());  // Holds the stdout output. Use `.toString()`.
                    console.log('------')
                }

                if (renameInstaller || fs.exists('../../system/ee/installer')) {
                    fs.rename('../../system/ee/installer', '../../system/ee/_installer');
                }

                // Load content from dump
                return db.load(config.env.DB_DUMP).then(() => {;
                    // Store database changes to skip initDb step in subsequent test runs
                    return db.dump(tempSeed);
                });

                // return db.load(config.env.DB_DUMP)
            })
        }
    })

    on('task', {
        'db:load': (file) => {
            fs.delete('../../system/user/cache/default_site/');
            return db.load(file)
        }
    })

    on('task', {
        'cache:clear': () => {
            fs.delete('../../system/user/cache/default_site/');
            return true
        }
    })

    on('task', {
        'db:query': (sql) => {
            return db.query(sql)
        }
    })

    on('task', {
        'filesystem:copy': ({ from, to }) => {
            return fs.copy(from, to);
        }
    })

    on('task', {
        'filesystem:create': (target) => {
            return fs.create(target);
        }
    })

    on('task', {
        'filesystem:createFile': (target) => {
            return fs.createFile(target);
        }
    })

    on('task', {
        'filesystem:delete': (target) => {
            return fs.delete(target);
        }
    })

    on('task', {
        'filesystem:count': (target) => {
            return fs.count(target);
        }
    })

    on('task', {
        'filesystem:path': (target) => {
            return fs.path(target);
        }
    })

    on('task', {
        'filesystem:list': ({target, mask='/*'}) => {
            return fs.list({target, mask});
        }
    })

    on('task', {
        'filesystem:info': (file) => {
            return fs.info(file);
        }
    })

    on('task', {
        'filesystem:exists': (file) => {
            return fs.exists(file);
        }
    })

    on('task', {
        'filesystem:read': (file) => {
            return fs.read(file);
        }
    })

    on('task', {
        'filesystem:rename': ({from, to}) => {
            return fs.rename(from, to);
        }
    })

    on('task', {
        'ee:config': ({ item, value, site_id }) => {
            if (!item) {
                return;
            }

            let command = [
                `cd support/fixtures && php config.php ${item}`,
                (value) ? ` '${value}'` : '',
                (site_id) ? ` --site-id ${site_id}` : ''
            ].join();

            /*
            if (value) {
                command += ` '${value}'`
            }

            if (site_id) {
                command += ` --site-id ${site_id}`
            }
            */
            Cypress.exec(command)
        }
    })

    on('task', {
        'db:relationships_specified_channels': () => {
            return db.seed('channel_sets/relationships-specified-channels.sql')
        }
    })

    on('task', {
        'installer:enable': () => {
            return installer.enable_installer()
        }
    })

    on('task', {
        'installer:disable': () => {
            return installer.disable_installer()
        }
    })

    on('task', {
        'installer:create_config': () => {
            return installer.create_config()
        }
    })

    on('task', {
        'installer:test': () => {
            return 'testing';
        }
    })

    on('task', {
        'installer:replace_config': ({file, options} = {}) => {
            if (typeof(options)==='undefined') {
                options = {database: db_defaults};
            }
            installer.replace_config(file, options)
            installer.set_base_url(config.baseUrl)
            return true;
        }
    })

    on('task', {
        'installer:revert_config': () => {
            return installer.revert_config()
        }
    })

    on('task', {
        'installer:replace_database_config': ({file, options}) => {
            return installer.replace_database_config(file, options, db_defaults)
        }
    })

    on('task', {
        'installer:revert_database_config': () => {
            return installer.revert_database_config()
        }
    })

    on('task', {
        'installer:delete_database_config': () => {
            return installer.delete_database_config()
        }
    })

    on('task', {
        'installer:backup_templates': () => {
            return installer.backup_templates()
        }
    })

    on('task', {
        'installer:restore_templates': () => {
            return installer.restore_templates()
        }
    })

    on('task', {
        'installer:version': () => {
            return installer.version()
        }
    })

    on('task', {
        'updater:backup_files': () => {
            return updater.backup_files()
        }
    })

    on('task', {
        'updater:restore_files': () => {
            return updater.restore_files()
        }
    })

    on("task", {
        generateOTP: require("cypress-otp")
    });


    on('before:browser:launch', (browser, launchOptions) => {
        launchOptions.args = consoleLog.browserLaunchHandler(
            browser,
            launchOptions.args
        )

        if (browser.name === 'chrome') {
            prepareAudit(launchOptions);
        }
        if (browser.name === 'chrome' && browser.isHeadless) {
            launchOptions.args.push('--disable-gpu');

            launchOptions.args.push('--disable-background-networking')
            launchOptions.args.push('--enable-features=NetworkService,NetworkServiceInProcess')
            launchOptions.args.push('--disable-background-timer-throttling')
            launchOptions.args.push('--disable-backgrounding-occluded-windows')
            launchOptions.args.push('--disable-breakpad')
            launchOptions.args.push('--disable-client-side-phishing-detection')
            launchOptions.args.push('--disable-default-apps')
            launchOptions.args.push('--disable-dev-shm-usage')
            launchOptions.args.push('--disable-extensions')
            launchOptions.args.push('--disable-features=site-per-process,TranslateUI')
            launchOptions.args.push('--disable-hang-monitor')
            launchOptions.args.push('--disable-ipc-flooding-protection')
            launchOptions.args.push('--disable-popup-blocking')
            launchOptions.args.push('--disable-prompt-on-repost')
            launchOptions.args.push('--disable-renderer-backgrounding')
            launchOptions.args.push('--disable-sync')
            launchOptions.args.push('--force-color-profile=srgb')
            launchOptions.args.push('--metrics-recording-only')
            launchOptions.args.push('--no-first-run')
            launchOptions.args.push('--safebrowsing-disable-auto-update')
            launchOptions.args.push('--enable-automation')
            launchOptions.args.push('--password-store=basic')
            launchOptions.args.push('--use-mock-keychain')
            launchOptions.args.push('--webview-disable-safebrowsing-support')

            return launchOptions
        }
    });

    on('task', {
        lighthouse: lighthouse()
    });

    return config;
}
