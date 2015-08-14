require './bootstrap.rb'

# Note: Tests need `@page.load` to be called manually since we're manipulating
# files before testing the upgrade. Please do not add `@page.load` to any of the
# `before` calls.

feature 'Updater' do
  before :all do
    @installer = Installer::Prepare.new
    @installer.enable_installer

    @database = File.expand_path('../circleci/database-2.10.1.php')
    @config = File.expand_path('../circleci/config-2.10.1.php')
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
  end

  it 'updates and creates a mailing list export when updating from 2.x to 3.x with the mailing list module' do
    clean_db do
      $db.query(IO.read('sql/database_2.10.1-mailinglist.sql'))
      clear_db_result
    end

    test_update(true)
  end

  it 'updates successfully when updating from 2.1.3 to 3.x' do
    @installer.revert_config
    @installer.replace_config(File.expand_path('../circleci/config-2.1.3.php'))
    @installer.revert_database_config
    @installer.replace_database_config(File.expand_path('../circleci/database-2.1.3.php'))

    clean_db do
      $db.query(IO.read('sql/database_2.1.3.sql'))
      clear_db_result
    end

    test_update
  end

  def test_update(mailinglist = false)
    page.driver.allow_url($test_config[:app_host])

    # Delete any stored mailing lists
    mailing_list_zip = File.expand_path('../../system/user/cache/mailing_list.zip')
    File.delete(mailing_list_zip) if File.exist?(mailing_list_zip)

    @page.load

    @page.should have(0).inline_errors
    @page.header.text.should match /Update ExpressionEngine \d+\.\d+\.\d+ to \d+\.\d+\.\d+/
    @page.submit.click

    @page.header.text.should match /Updating ExpressionEngine \d+\.\d+\.\d+ to \d+\.\d+\.\d+/
    @page.req_title.text.should include 'Processing | Step 2 of 3'

    # Sleep until ready
    while @page.req_title.text.include? 'Processing'
      sleep 1
    end

    @page.header.text.should match /ExpressionEngine Updated to \d+\.\d+\.\d+/
    @page.req_title.text.should include 'Completed'
    @page.has_submit?.should == true

    @page.has_login?.should == true

    if mailinglist == false
      @page.has_download?.should == false
    else
      @page.has_download?.should == true
      File.exist?(mailing_list_zip).should == true
    end
  end
end
