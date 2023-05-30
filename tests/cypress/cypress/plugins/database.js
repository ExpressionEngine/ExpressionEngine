const fs = require('fs');
const path = require('path');
const mysql = require('mysql2');
const mysqldump = require('mysqldump');

class Database {
    constructor(config) {
        // create the connection to database
        this.path = __dirname + './../../support/sql/';
        this.config = Object.assign(config, { multipleStatements: true });
        this.connection = mysql.createPool(this.config);
    }

    sqlPath(file) {
        return path.resolve(this.path + file)
    }

    readSQL(file) {
        return fs.readFileSync(this.sqlPath(file), 'utf8');
    }

    query(sql) {
        return this.connection.promise().query(sql);
    }

    load(file) {
        return this.query(this.readSQL(file));
    }

    truncate() {
        return this.load('truncate_db.sql');
    }

    seed(file) {
        let self = this;

        return this.truncate().then(function(result) {
            return self.load(file);
        }).then(function(result) {
            // console.log("DB loaded file " + file);
            //self.connection.end();
            return true;
        });
    }

    dump(file) {
        return mysqldump({
            connection: {
                host: this.config.host,
                port: this.config.port,
                user: this.config.user,
                password: this.config.password,
                database: this.config.database,
            },
            dump: {
                data: {
                    maxRowsPerInsertStatement: 100,
                },
            },
            dumpToFile: this.sqlPath(file),
        });
    }
}

module.exports = Database;