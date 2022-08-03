const fs = require('fs');
const fse = require('fs-extra');
const glob = require("glob")
const del = require('del');
const path = require('path');

class Filesystem {
    copy(from, to) {
        to = path.resolve(to);
        from = path.resolve(from);
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
        target = path.resolve(target);
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
        target = path.resolve(target);
        // console.log('FS Delete: ' + target);
        return del(target, { force: true });
    }

    count(target) {
        target = path.resolve(target);
        return glob.sync(target+'/*').length;
    }

    path(target) {
        return path.resolve(target);
    }

    list({target, mask='/*'}) {
        target = path.resolve(target);
        return glob.sync(target+mask);
    }

    info(file) {
        return fs.statSync(file);
    }

    exists(file) {
        return fs.existsSync(file);
    }

    read(file) {
        return fs.readFileSync(path.resolve(file), "utf8");
    }

    rename(source, target) {
        fs.renameSync(path.resolve(source), path.resolve(target));
        return true;
    }
}

module.exports = Filesystem;
