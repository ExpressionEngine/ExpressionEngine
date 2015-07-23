module Installer
  # Helps prepare the Installer for rspec tests
  class Prepare
    attr_reader :boot, :wizard

    def initialize
      system = '../../system/'
      @boot     = File.expand_path('ee/EllisLab/ExpressionEngine/Boot/boot.php', system)
      @config   = File.expand_path('user/config/config.php', system)
      @database = File.expand_path('user/config/database.php', system)
      @wizard   = File.expand_path('ee/installer/controllers/wizard.php', system)
    end

    # Enables installer by removing `FALSE &&` from boot.php
    def enable_installer
      swap(
        @boot,
        "if (FALSE && defined('REQ') && REQ == 'CP' && is_dir(SYSPATH.'ee/installer/'))",
        "if (defined('REQ') && REQ == 'CP' && is_dir(SYSPATH.'ee/installer/'))"
      )
    end

    # Disables installer by adding `FALSE &&` to boot.php
    def disable_installer
      swap(
        @boot,
        "if (defined('REQ') && REQ == 'CP' && is_dir(SYSPATH.'ee/installer/'))",
        "if (FALSE && defined('REQ') && REQ == 'CP' && is_dir(SYSPATH.'ee/installer/'))"
      )
    end

    # Disables install directory rename post-install/upgrade
    def disable_rename
      swap(
        @wizard,
        'return rename(APPPATH, $new_path);',
        '// return rename(APPPATH, $new_path);'
      )
    end

    # Enables install directory rename post-install/upgrade
    def enable_rename
      swap(
        @wizard,
        '// return rename(APPPATH, $new_path);',
        'return rename(APPPATH, $new_path);'
      )
    end

    def replace_config(file = '')
      File.rename(@config, @config + '.tmp') if File.exist?(@config)
      FileUtils.cp(file, @config) if File.exist?(file)
    end

    def revert_config
      config_temp = @config + '.tmp'
      return unless File.exist?(config_temp) # Problem
      # Since this file exists that cp above likely isn't working...why not?

      File.delete(@config) if File.exist?(@config)
      File.rename(config_temp, @config)
    end

    def replace_database_config(file = '')
      File.rename(@database, @database + '.tmp') if File.exist?(@database)
      FileUtils.cp(file, @database) if File.exist?(file)

      # Replace important values
      swap(
        @database,
        "['hostname'] = 'localhost';",
        "['hostname'] = '#{$test_config[:db_host]}';"
      )
      swap(
        @database,
        "['database'] = 'circle_test';",
        "['database'] = '#{$test_config[:db_name]}';"
      )
      swap(
        @database,
        "['username'] = 'ubuntu';",
        "['username'] = '#{$test_config[:db_username]}';"
      )
      swap(
        @database,
        "['password'] = '';",
        "['password'] = '#{$test_config[:db_password]}';"
      )
    end

    def revert_database_config
      database_temp = @database + '.tmp'
      return unless File.exist?(database_temp)

      File.delete(@database) if File.exist?(@database)
      File.rename(database_temp, @database)
    end

    def version=(version)
      swap(
        @config,
        /\$config\['app_version'\] = '.*?';/i,
        "$config['app_version'] = '#{version}';"
      )
    end

    private

    # Swaps on piece of text for another given a file
    #
    # @param [File] file File object
    # @param [String] pattern Text to find
    # @param [String] replacement Replacement of above text
    # @return [nil]
    def swap(file, pattern, replacement)
      file = File.expand_path(file)
      temp = File.read(file).gsub(pattern, replacement)
      File.open(file, 'w') { |f| f.puts temp }
    end
  end
end
