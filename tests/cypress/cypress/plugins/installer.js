const fs = require('fs');
const fse = require('fs-extra');
const glob = require("glob")
const del = require('del');
const path = require('path');
const { SSL_OP_SSLEAY_080_CLIENT_DH_BUG } = require('constants');

const system             = '../../system/';
const env               = system + '../.env.php'
const config            = system + 'user/config/config.php'
const database          = system + 'user/config/database.php'
const wizard            = system + 'ee/installer/controllers/wizard.php'
const old_templates     = system + 'user/templates/default_site.old'
const current_templates = system + 'user/templates/default_site'

class Installer {
	enable_installer() {
		let dotenv = fs.readFileSync(path.resolve(env), "utf8");
		dotenv = dotenv.replace("putenv('EE_INSTALL_MODE=FALSE');", "putenv('EE_INSTALL_MODE=TRUE');")
		fs.writeFileSync(path.resolve(env), dotenv)
		return true
	}

	disable_installer() {
		let dotenv = fs.readFileSync(path.resolve(env), "utf8");
		dotenv = dotenv.replace("putenv('EE_INSTALL_MODE=TRUE');", "putenv('EE_INSTALL_MODE=FALSE');")
		fs.writeFileSync(path.resolve(env), dotenv)
		return true
	}

	// Replace the current config file with another, while backing up the
	// previous one (e.g. config.php.tmp). Can be reverted by using revert_config
	//
	// @param [Type] file The path to the config file you want to use, set to blank to only move existing file
	// @return [void]
	replace_config(file = '', options = { attempt: 0 }) {
		if (typeof(options.attempt)==='undefined') {
			options.attempt = 0;
		}

		// Only save the original file if this is our first attempt
		if (fs.existsSync(config) && options.attempt == 0) {
			fs.renameSync(config, config + '.tmp')
		} else if (fs.existsSync(config)) {
			fs.unlinkSync(config)
		}

		fse.copySync(path.resolve(file), path.resolve(config));
		fs.chmodSync(path.resolve(config), 666);

		let config_contents = fs.readFileSync(path.resolve(config), "utf8");

		// Check file contents for the correct app_version, try again if it fails
		if (typeof(options.app_version)!=='undefined' && options.attempt < 5) {
			if(!config_contents.includes(options.app_version)) {
				options.attempt += 1
				replace_config(file, options)
			}

			if (options.attempt != 0) {
				return;
			}
		}

		//return if file.empty?

		// Check for database options
		if (typeof(options.database)!=='undefined') {
			config_contents = fs.readFileSync(path.resolve(config), "utf8");
			for (const property in options.database) {
				config_contents = config_contents.replace(
					/'#{property}' => .*?,/,
					"'#{property}' => '#{config_contents[property]}',"
				)
			}
		}

		for (const property in options) {
			if (property != 'database' && property != 'app_version') {
				config_contents = config_contents.replace(
					/\$config\['#{property}'\]\s+=\s+.*?;/,
					"$config['#{property}'] = '#{config_contents[property]}';"
				)
			}
		}

		fs.writeFileSync(path.resolve(config), config_contents);

		return true;
	}

	create_config() {
		fs.writeFileSync(path.resolve(config), '');
		fs.chmodSync(config, 666);
		return true
	}

	// Revert the current config file to the previous (config.php.tmp)
	//
	// @return [void]
	revert_config() {
		const config_temp = config + '.tmp'
		if (fs.existsSync(config_temp)) {
			if (fs.existsSync(config)) {
				fs.unlinkSync(path.resolve(config))
			}
			fs.renameSync(config_temp, config)
		}
		return true
	}

	delete_database_config() {
		if (fs.existsSync(database)) {
			fs.chmodSync(database, 666);
			fs.unlinkSync(database)
		}
		return true
	}
/*
# Replaces current database config with file of your choice
#
# @param [String] file Path to file you want, ideally use File.expand_path
# @param [Hash] options Hash of options for replacing
# @return [void]
def replace_database_config(file, options = {})
  File.rename(@database, @database + '.tmp') if File.exist?(@database)
  FileUtils.cp(file, @database) if File.exist?(file)
  FileUtils.chmod(666, @database) if File.exist?(@database)

  # Replace important values
  return unless File.exist?(file)

  defaults = {
    database: $test_config[:db_name],
    dbdriver: 'mysqli',
    hostname: $test_config[:db_host],
    password: $test_config[:db_password],
    username: $test_config[:db_username]
  }

  defaults.merge(options).each do |key, value|
    swap(
      @database,
      /\['#{key}'\] = '.*?';/,
      "['#{key}'] = '#{value}';"
    )
  end
end

# Revert current database config to previous (database.php.tmp)
#
# @return [void]
def revert_database_config
  database_temp = @database + '.tmp'
  return unless File.exist?(database_temp)

  File.delete(@database) if File.exist?(@database)
  File.rename(database_temp, @database)
end

# Set the version in the config file to something else
#
# @param [Number] version The semver verison number you want to use
# @return [void]
def version=(version)
  swap(
    @config,
    /\$config\['app_version'\] = '.*?';/i,
    "$config['app_version'] = '#{version}';"
  )
end
*/
	// Backup any templates for restoration later
	//
	// @return [void]
	backup_templates() {
		del(old_templates, { force: true });
		if (fs.existsSync(current_templates)) {
			fs.renameSync(current_templates, old_templates)
		}
		return true;
	}

	// Restore templates if they've previously been backed up
	//
	// @return [void]
	restore_templates() {
		del(current_templates, { force: true });
		if (fs.existsSync(old_templates)) {
			fs.renameSync(old_templates, current_templates)
		}
		return true;
	}
}

module.exports = Installer;