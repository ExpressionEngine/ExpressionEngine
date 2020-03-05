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

    const Filesystem = require('./filesystem.js');
    const fs = new Filesystem;

    on('task', {
        'db:seed': () => {
            return db.seed('database.sql')
        }
    })

    on('task', {
        'db:load': (file) => {
            return db.load(file)
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
}