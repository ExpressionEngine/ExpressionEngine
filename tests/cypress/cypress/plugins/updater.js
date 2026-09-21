const fse = require('fs-extra');

const basePath = '../../';
const backupPath = `${basePath}one_click_backup/`;
const preserveFolders = ['system', 'themes', 'images'];
const configPath = `${basePath}system/user/config/config.php`;
const handoffPath = `${basePath}system/ee/updater`;

class Updater {
    configure_handoff({ sessionType, csrfDisabled }) {
        if (!['c', 'cs', 's'].includes(sessionType) || typeof csrfDisabled !== 'boolean') {
            throw new Error('Invalid updater handoff configuration');
        }
        if (this.handoffConfig || fse.existsSync(handoffPath)) {
            throw new Error('An updater is already present; refusing to replace it');
        }

        this.handoffConfig = fse.readFileSync(configPath);
        const version = JSON.parse(fse.readFileSync(`${basePath}build-tools/build.json`, 'utf8')).tag;
        fse.appendFileSync(configPath, `
$config['app_version'] = '${version}';
$config['cp_session_type'] = '${sessionType}';
$config['disable_csrf_protection'] = '${csrfDisabled ? 'y' : 'n'}';
`);
        return true;
    }

    install_handoff() {
        if (!this.handoffConfig || fse.existsSync(handoffPath)) {
            throw new Error('Configure the handoff before installing its fixture');
        }

        const source = ['installer', '_installer'].map(name => `${basePath}system/ee/${name}/updater`)
            .find(directory => fse.existsSync(directory));
        if (!source) {
            throw new Error('Unable to locate the updater source');
        }

        const staged = `${handoffPath}.cypress`;
        try {
            fse.copySync(source, staged);
            // Release packaging supplies these dependencies; source checkouts need them too.
            for (const file of ['Boot/boot.common.php', 'Core/Autoloader.php']) {
                const contents = fse.readFileSync(`${basePath}system/ee/ExpressionEngine/${file}`, 'utf8')
                    .replace(/(namespace|use) ExpressionEngine\\/g, '$1 ExpressionEngine\\Updater\\');
                fse.outputFileSync(`${staged}/ExpressionEngine/Updater/${file}`, contents);
            }
            // Keep the real boot, CP authorization and request state; replace only destructive update work.
            fse.copySync('support/fixtures/updater/Runner.php',
                `${staged}/ExpressionEngine/Updater/Service/Updater/Runner.php`);
            fse.writeFileSync(`${staged}/.cypress-handoff`, 'fixture');
            fse.chmodSync(staged, 0o777);
            fse.renameSync(staged, handoffPath);
        } finally {
            fse.removeSync(staged);
        }
        return true;
    }

    restore_handoff() {
        if (this.handoffConfig) {
            if (fse.existsSync(`${handoffPath}/.cypress-handoff`)) {
                fse.removeSync(handoffPath);
            }
            fse.writeFileSync(configPath, this.handoffConfig);
            this.handoffConfig = null;
        }
        return true;
    }

    backup_files() {
        // Make a copy of everything
        preserveFolders.forEach(function(folder) {
            fse.copySync(`${basePath}${folder}`, `${backupPath}${folder}`, { 
                overwrite: true 
            });
        });

        return true
    }

    restore_files() {
        // Restore a copy of everything
        preserveFolders.forEach(function(folder) {
            fse.copySync(`${backupPath}${folder}`, `${basePath}${folder}`, { 
                overwrite: true 
            });
        });
        fse.removeSync(backupPath);
        return true
    }
}

module.exports = Updater;
