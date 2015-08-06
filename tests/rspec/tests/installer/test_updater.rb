require './bootstrap.rb'

# Note: Tests need `@page.load` to be called manually since we're manipulating
# files before testing the upgrade. Please do not add `@page.load` to any of the
# `before` calls.

feature 'Updater' do
  before :all do
    @installer = Installer::Prepare.new
    @installer.enable_installer
    @installer.disable_rename

    @database = File.expand_path('../circleci/database_2x.php')
    @config = File.expand_path('../circleci/config_2x.php')
  end

  before :each do
    @installer.replace_config(@config)
    @installer.replace_database_config(@database)

    @version = '2.20.0'
    @installer.version = @version

    @page = Installer::Updater.new
    no_php_js_errors
  end

  after :each do
    @installer.revert_config
    @installer.revert_database_config
    FileUtils.rm_rf '../../system/user/templates/default_site/'
  end

  after :all do
    @installer.disable_installer
    @installer.enable_rename
    @installer.delete_database_config
  end

  it 'appears when using a database.php file' do
    @page.load
    @page.should have(0).inline_errors
    @page.header.text.should match /Update ExpressionEngine \d+\.\d+\.\d+ to \d+\.\d+\.\d+/
  end

  it 'shows an error when no database information exists at all' do
    @installer.delete_database_config
    @page.load
    @page.header.text.should match /Error While Installing \d+\.\d+\.\d+/
    @page.error.text.should include "Unable to locate any database connection information."
  end

  context 'when updating from 2.x to 3.x' do
    it 'updates using mysql as the dbdriver' do
      @installer.replace_database_config(@database, dbdriver: 'mysql')
      test_update
    end

    it 'updates using localhost as the database host' do
      @installer.replace_database_config(@database, hostname: 'localhost')
      test_update
    end

    it 'updates using 127.0.0.1 as the database host' do
      @installer.replace_database_config(@database, hostname: '127.0.0.1')
      test_update
    end

    it 'updates using old template basepath' do
      @installer.revert_config
      @installer.replace_config(@config, tmpl_file_basepath: '../system/expressionengine/templates')
      test_update
      File.exist?('../../system/user/templates/default_site/').should == true
      File.exist?('../../system/expressionengine/templates/default_site/').should == false
    end

    it 'updates using new template basepath' do
      @installer.revert_config
      @installer.replace_config(@config, tmpl_file_basepath: '../system/user/templates')
      test_update
      File.exist?('../../system/user/templates/default_site/').should == true
    end

    def test_update
      @page.load

      @page.should have(0).inline_errors
      @page.header.text.should match /Update ExpressionEngine \d+\.\d+\.\d+ to \d+\.\d+\.\d+/
      @page.submit.click

      @page.header.text.should match /Updating ExpressionEngine \d+\.\d+\.\d+ to \d+\.\d+\.\d+/
      @page.req_title.text.should include 'Processing | Step 2 of 3'

      sleep 1 # Wait for the updater to finish

      @page.header.text.should match /ExpressionEngine Updated to \d+\.\d+\.\d+/
      @page.req_title.text.should include 'Completed'
      @page.has_submit?.should == true

      # Database dump has mailing list and should provide the download button
      # and the zip file
      @page.has_login?.should == true
      @page.has_download?.should == true
      File.exist?('../../system/user/cache/mailing_list.zip').should == true
    end
  end

# Override base reset_db method to import 2.10.1 database
def reset_db
  clean_db do
    $db.query(IO.read('sql/database_2.10.1.sql'))
    clear_db_result
  end
end
