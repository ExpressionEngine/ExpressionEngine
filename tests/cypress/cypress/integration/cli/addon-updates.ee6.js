/// <reference types="Cypress" />

const addonPath = '../../system/user/addons/cypress_cli_update';
const extensionClass = 'Cypress_cli_update_ext';
const cli = 'php ../../system/ee/eecli.php';

function runCli(command) {
    return cy.exec(`${cli} ${command}`).then((result) => {
        expect(result.code).to.eq(0);
        expect(result.stderr).to.be.empty;
        expect(result.stdout).not.to.match(/Fatal error|caught:|on line/);
    });
}

function registerPlugin() {
    return cy.task('db:query', `
        INSERT INTO exp_plugins
            (plugin_name, plugin_package, plugin_version, is_typography_related)
        VALUES ('Old plugin name', 'cypress_cli_update', '1.0.0', 'n')
    `);
}

function expectUpdatedHooks(enabled = 'y') {
    cy.task('db:query', `
        SELECT hook, version, enabled FROM exp_extensions
        WHERE class = '${extensionClass}' ORDER BY hook
    `).then(([rows]) => {
        expect(rows).to.deep.equal([
            {hook: 'cypress_cli_new_one', version: '2.0.0', enabled},
            {hook: 'cypress_cli_new_two', version: '2.0.0', enabled},
        ]);
    });
}

function expectUpdatedPlugin() {
    cy.task('db:query', `
        SELECT plugin_name, plugin_package, plugin_version, is_typography_related
        FROM exp_plugins WHERE plugin_package = 'cypress_cli_update'
    `).then(([rows]) => {
        expect(rows).to.deep.equal([{
            plugin_name: 'Cypress CLI Update',
            plugin_package: 'cypress_cli_update',
            plugin_version: '2.0.0',
            is_typography_related: 'y',
        }]);
    });
}

context('CLI add-on update regressions', () => {
    beforeEach(() => {
        cy.task('db:seed');
        cy.task('filesystem:copy', {
            from: 'support/cli/cypress_cli_update',
            to: '../../system/user/addons/',
        });
        cy.task('db:query', `
            INSERT INTO exp_extensions
                (class, method, hook, settings, priority, version, enabled)
            VALUES ('${extensionClass}', 'test_hook', 'cypress_cli_old', '', 10, '1.0.0', 'y')
        `);
    });

    afterEach(() => {
        cy.task('filesystem:delete', addonPath);
    });

    ['--addon cypress_cli_update', '--all'].forEach((option) => {
        it(`runs extension migrations with the installed version (${option})`, () => {
            registerPlugin().then(([result]) => {
                const pluginId = result.insertId;
                runCli(`addons:update ${option}`);
                expectUpdatedHooks();
                expectUpdatedPlugin();
                cy.task('db:query', `
                    SELECT plugin_id FROM exp_plugins WHERE plugin_package = 'cypress_cli_update'
                `).then(([rows]) => {
                    expect(rows).to.deep.equal([{plugin_id: pluginId}]);
                });
            });
        });
    });

    it('leaves an absent extension uninstalled when updating a fieldtype', () => {
        cy.task('filesystem:copy', {
            from: 'support/cli/ft.cypress_cli_update.php',
            to: addonPath,
        });
        cy.task('db:query', `
            INSERT INTO exp_fieldtypes (name, version)
            VALUES ('cypress_cli_update', '1.0.0')
        `);
        cy.task('db:query', `DELETE FROM exp_extensions WHERE class = '${extensionClass}'`);

        runCli('addons:update --addon cypress_cli_update');

        cy.task('db:query', "SELECT version FROM exp_fieldtypes WHERE name = 'cypress_cli_update'")
            .then(([rows]) => expect(rows).to.deep.equal([{version: '2.0.0'}]));
        cy.task('db:query', `SELECT hook FROM exp_extensions WHERE class = '${extensionClass}'`)
            .then(([rows]) => expect(rows).to.be.empty);
    });

    it('keeps replacement hooks disabled when the extension was disabled', () => {
        registerPlugin();
        cy.task('db:query', `UPDATE exp_extensions SET enabled = 'n' WHERE class = '${extensionClass}'`);

        runCli('addons:update --addon cypress_cli_update');

        expectUpdatedHooks('n');
    });

    it('registers a missing plugin while updating an installed extension', () => {
        runCli('addons:update --addon cypress_cli_update');
        expectUpdatedPlugin();
        expectUpdatedHooks();
    });

    it('continues to update extension-only add-ons', () => {
        cy.task('filesystem:delete', `${addonPath}/pi.cypress_cli_update.php`);
        runCli('addons:update --addon cypress_cli_update');
        expectUpdatedHooks();
        cy.task('db:query', "SELECT * FROM exp_plugins WHERE plugin_package = 'cypress_cli_update'")
            .then(([rows]) => expect(rows).to.be.empty);
    });

    it('continues to update plugin-only add-ons', () => {
        registerPlugin();
        cy.task('filesystem:delete', `${addonPath}/ext.cypress_cli_update.php`);
        cy.task('db:query', `DELETE FROM exp_extensions WHERE class = '${extensionClass}'`);
        runCli('addons:update --addon cypress_cli_update');
        expectUpdatedPlugin();
    });

    it('deletes a legacy field and its columns when its separate table is absent', () => {
        cy.task('db:query', 'DROP TABLE IF EXISTS exp_channel_data_field_6');
        cy.task('db:query', "SHOW COLUMNS FROM exp_channel_data WHERE Field IN ('field_id_6', 'field_ft_6')")
            .then(([rows]) => expect(rows).to.have.length(2));

        runCli('cypress:delete-field --field-id 6');

        cy.task('db:query', 'SELECT * FROM exp_channel_fields WHERE field_id = 6')
            .then(([rows]) => expect(rows).to.be.empty);
        cy.task('db:query', "SHOW COLUMNS FROM exp_channel_data WHERE Field IN ('field_id_6', 'field_ft_6')")
            .then(([rows]) => expect(rows).to.be.empty);
        cy.task('db:query', "SHOW COLUMNS FROM exp_channel_data WHERE Field IN ('field_id_1', 'field_ft_1')")
            .then(([rows]) => expect(rows).to.have.length(2));
        cy.task('db:query', "SELECT * FROM exp_update_log WHERE message LIKE '%exp_channel_data_field_6%does not exist%'")
            .then(([rows]) => expect(rows).to.have.length(1));
    });

    it('preserves the existing installer logger when loading Smartforge', () => {
        cy.exec('php support/fixtures/smartforgeInstaller.php').then((result) => {
            expect(result.code).to.eq(0);
            expect(result.stderr).to.be.empty;
            expect(JSON.parse(result.stdout)).to.deep.equal({logger_preserved: true});
        });
    });

    it('continues to delete fields that have a separate storage table', () => {
        cy.task('db:query', "SHOW TABLES LIKE 'exp_channel_data_field_8'")
            .then(([rows]) => expect(rows).to.have.length(1));

        runCli('cypress:delete-field --field-id 8');

        cy.task('db:query', 'SELECT * FROM exp_channel_fields WHERE field_id = 8')
            .then(([rows]) => expect(rows).to.be.empty);
        cy.task('db:query', "SHOW TABLES LIKE 'exp_channel_data_field_8'")
            .then(([rows]) => expect(rows).to.be.empty);
    });
});
