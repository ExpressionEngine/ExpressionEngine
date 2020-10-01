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

    const baseUrl = config.env.CYPRESS_BASE_URL || null;
    if (baseUrl) {
        config.baseUrl = baseUrl;
    }

    on('task', {
        'db:clear': () => {
            return db.truncate()
        }
    })

    on('task', {
        'db:seed': () => {
            fs.delete('../../system/user/cache/default_site/');
            return db.seed(config.env.DB_DUMP)
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
        'installer:replace_config': ({file, options}) => {
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



    on('before:browser:launch', (browser, launchOptions) => {
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

    return config;
}