module Installer
  # Helps prepare the Installer for rspec tests
  class Prepare
    attr_reader :env, :wizard

    def initialize
      system             = '../../system/'
      @env               = File.expand_path('../.env.php', system)
      @config            = File.expand_path('user/config/config.php', system)
      @database          = File.expand_path('user/config/database.php', system)
      @wizard            = File.expand_path('ee/installer/controllers/wizard.php', system)
      @old_templates     = File.expand_path('user/templates/default_site.old', system)
      @current_templates = File.expand_path('user/templates/default_site', system)
    end

    def enable_installer
      swap(
        @env,
        "putenv('EE_INSTALL_MODE=FALSE');",
        "putenv('EE_INSTALL_MODE=TRUE');"
      )
    end

    def disable_installer
      swap(
        @env,
        "putenv('EE_INSTALL_MODE=TRUE');",
        "putenv('EE_INSTALL_MODE=FALSE');"
      )
    end

    # Replace the current config file with another, while backing up the
    # previous one (e.g. config.php.tmp). Can be reverted by using revert_config
    #
    # @param [Type] file The path to the config file you want to use, set to blank to only move existing file
    # @return [void]
    def replace_config(file = '', options = { attempt: 0 })
      options[:attempt] = options.key?(:attempt) ? options[:attempt] : 0

      # Only save the original file if this is our first attempt
      if File.exist?(@config) && options[:attempt] == 0
        File.rename(@config, @config + '.tmp')
      elsif File.exist?(@config)
        File.delete(@config)
      end

      FileUtils.cp(file, @config) if File.exist?(file)
      FileUtils.chmod(0666, @config) if File.exist?(@config)

      # Check file contents for the correct app_version, try again if it fails
      if options[:app_version] && options[:attempt] < 5
        unless File.read(@config).include?(options[:app_version])
          options[:attempt] += 1
          replace_config(file, options)
        end

        return if options[:attempt] != 0
      end

      return if file.empty?

      # Check for database options
      if options[:database]
        options[:database].each do |key, value|
          swap(
            @config,
            /'#{key}' => .*?,/,
            "'#{key}' => '#{value}',"
          )
        end

        options.delete(:database)
      end

      options.delete(:app_version)
      options.each do |key, value|
        swap(
          @config,
          /\$config\['#{key}'\]\s+=\s+.*?;/,
          "$config['#{key}'] = '#{value}';"
        )
      end
    end

    # Revert the current config file to the previous (config.php.tmp)
    #
    # @return [void]
    def revert_config
      config_temp = @config + '.tmp'
      return unless File.exist?(config_temp)

      File.delete(@config) if File.exist?(@config)
      File.rename(config_temp, @config)
    end

    def delete_database_config
      FileUtils.chmod(0666, @database) if File.exist?(@database)
      FileUtils.rm @database if File.exist?(@database)
    end

    # Replaces current database config with file of your choice
    #
    # @param [String] file Path to file you want, ideally use File.expand_path
    # @param [Hash] options Hash of options for replacing
    # @return [void]
    def replace_database_config(file, options = {})
      File.rename(@database, @database + '.tmp') if File.exist?(@database)
      FileUtils.cp(file, @database) if File.exist?(file)
      FileUtils.chmod(0666, @database) if File.exist?(@database)

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

    # Backup any templates for restoration later
    #
    # @return [void]
    def backup_templates
      FileUtils.rm_rf @old_templates if File.exist? @old_templates

      if File.exist? @current_templates
        FileUtils.mv(
          @current_templates,
          @old_templates
        )
      end
    end

    # Restore templates if they've previously been backed up
    #
    # @return [void]
    def restore_templates
      FileUtils.rm_rf @current_templates if File.exist? @current_templates

      if File.exist? @old_templates
        FileUtils.mv(
          @old_templates,
          @current_templates
        )
      end
    end
  end
end
