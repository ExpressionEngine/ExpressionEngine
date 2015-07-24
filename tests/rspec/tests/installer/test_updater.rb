require './bootstrap.rb'

feature 'Updater' do
  before :all do
    ENV['updater'] = 'true'

    @installer = Installer::Prepare.new
    @installer.enable_installer
    @installer.disable_rename

    @database = File.expand_path('../circleci/database_2x.php')
  end

  before :each do
    @installer.replace_config(File.expand_path('../circleci/config_2x.php'))
    @installer.replace_database_config(@database)

    @version = '2.20.0'
    @installer.version = @version

    @page = Installer::Updater.new
    @page.load
    no_php_js_errors
  end

  after :each do
    @installer.revert_config
    @installer.revert_database_config
  end

  after :all do
    ENV.delete('updater')

    @installer.disable_installer
    @installer.enable_rename
  end

  it 'appears when using a database.php file' do
    @page.should have(0).inline_errors
    @page.header.text.should include "Update ExpressionEngine #{@version} to 3.0.0"
  end

  context 'when updating from 2.x to 3.x' do
    it 'upgrades using localhost as the database host' do
      @installer.replace_database_config(@database, 'localhost')

      @page.should have(0).inline_errors
      @page.header.text.should include "Update ExpressionEngine #{@version} to 3.0.0"
      @page.submit.click

      @page.header.text.should include "Updating ExpressionEngine #{@version} to 3.0.0"
      @page.req_title.text.should include 'Processing | Step 2 of 3'

      sleep 1 # Wait for the updater to finish

      @page.header.text.should include 'ExpressionEngine 3.0.0 Installed'
      @page.req_title.text.should include 'Completed'
      @page.has_submit?.should == true
    end

    it 'upgrades using 127.0.0.1 as the database host' do
      @installer.replace_database_config(@database, '127.0.0.1')

      @page.should have(0).inline_errors
      @page.header.text.should include "Update ExpressionEngine #{@version} to 3.0.0"
      @page.submit.click

      @page.header.text.should include "Updating ExpressionEngine #{@version} to 3.0.0"
      @page.req_title.text.should include 'Processing | Step 2 of 3'

      sleep 1 # Wait for the updater to finish

      @page.header.text.should include 'ExpressionEngine 3.0.0 Installed'
      @page.req_title.text.should include 'Completed'
      @page.has_submit?.should == true
    end
  end

end
