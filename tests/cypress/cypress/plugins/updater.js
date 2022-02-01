const fse = require('fs-extra');

const basePath = '../../';
const backupPath = `${basePath}one_click_backup/`;
const preserveFolders = ['system', 'themes', 'images'];

class Updater {
    backup_files() {
        // Make a copy of everything
        preserveFolders.forEach(function(folder) {
            console.log({ folder });
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