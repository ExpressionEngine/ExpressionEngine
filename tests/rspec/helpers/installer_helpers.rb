module Installer
  # Helps prepare the Installer for rspec tests
  class Prepare
    attr_reader :boot, :wizard

    def initialize
      @boot   = File.expand_path('../../system/ee/EllisLab/ExpressionEngine/Boot/boot.php')
      @config = File.expand_path('../../system/user/config/config.php')
      @wizard = File.expand_path('../../system/ee/installer/controllers/wizard.php')
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

    def version=(version)
      swap(
        @config,
        "$config['app_version'] = '3.0.0';",
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
