const fs = require('fs');
const path = require('path');
const mysql = require('mysql2');

class Database {
    constructor(config) {
        // create the connection to database
        this.path = __dirname + './../../support/sql/';
        config = Object.assign(config, { multipleStatements: true });
        this.connection = mysql.createPool(config);
    }

    readSQL(file) {
        return fs.readFileSync(path.resolve(this.path + file), 'utf8');
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
}

module.exports = Database;