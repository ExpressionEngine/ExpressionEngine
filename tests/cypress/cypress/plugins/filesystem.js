const fs = require('fs');
const fse = require('fs-extra');
const glob = require("glob")
const del = require('del');
const path = require('path');

class Filesystem {
    copy(from, to) {
        to = path.resolve(to).replace(/\\/g, '/');
        from = path.resolve(from).replace(/\\/g, '/');
        // console.log("FS Copy:", { from, to });

        let sources = glob.sync(from);
        sources.forEach(function(source) {
            let basename = path.basename(source);
            let destination = path.join(to, basename);
            fse.copySync(source, destination);
        });

        return true;
    }

    create(target) {
        target = path.resolve(target).replace(/\\/g, '/');
        // console.log('FS Create: ' + target);

        return fs.promises.mkdir(target, { recursive: true }).then(function() {
            return true;
        }).catch(function() {

        })
    }

    createFile(target, content = '') {
        target = path.resolve(target);
        fs.writeFileSync(target, content);
        return true;
    }

    delete(target) {
        target = path.resolve(target).replace(/\\/g, '/');
        // console.log('FS Delete: ' + target);
        return del(target, { force: true });
    }

    count(target) {
        target = path.resolve(target).replace(/\\/g, '/');
        return glob.sync(target+'/*').length;
    }

    path(target) {
        return path.resolve(target).replace(/\\/g, '/');
    }

    list({target, mask='/*'}) {
        target = path.resolve(target).replace(/\\/g, '/');
        return glob.sync(target+mask);
    }

    info(file) {
        return fs.statSync(file);
    }

    exists(file) {
        return fs.existsSync(file);
    }

    read(file) {
        return fs.readFileSync(path.resolve(file).replace(/\\/g, '/'), "utf8");
    }

    rename(source, target) {
        fs.renameSync(path.resolve(source).replace(/\\/g, '/'), path.resolve(target).replace(/\\/g, '/'));
        return true;
    }
}

module.exports = Filesystem;
