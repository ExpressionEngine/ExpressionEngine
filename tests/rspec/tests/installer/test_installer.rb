ENV['area'] = 'installer'
require './bootstrap.rb'

feature 'Installer' do
  before :all do
    # Make sure boot.php does not have the FALSE &&
    @boot = File.expand_path('../../system/ee/EllisLab/ExpressionEngine/Boot/boot.php')
    text = File.read(@boot).gsub(
      "if (FALSE && defined('REQ') && REQ == 'CP' && is_dir(SYSPATH.'ee/installer/'))",
      "if (defined('REQ') && REQ == 'CP' && is_dir(SYSPATH.'ee/installer/'))"
    )
    File.open(@boot, 'w') { |f| f.puts text }

    # Rename config.php temporarily
    @config = File.expand_path('../../system/user/config/config.php')
    @config_temp = File.expand_path('../../system/user/config/config.php.tmp')
    File.rename(@config, @config_temp)
    File.open(@config, 'w')

    @page = Installer.new
  end

  before :each do
    @page.load
    no_php_js_errors
  end

  after :all do
    # Add the FALSE && back into boot.php
    text = File.read(@boot).gsub(
      "if (defined('REQ') && REQ == 'CP' && is_dir(SYSPATH.'ee/installer/'))",
      "if (FALSE && defined('REQ') && REQ == 'CP' && is_dir(SYSPATH.'ee/installer/'))"
    )
    File.open(@boot, 'w') { |f| f.puts text }

    # Put config.php back
    File.delete(@config)
    File.rename(@config_temp, @config)
  end

  it 'should load installer' do
    @page.all_there?.should == true
  end
end
