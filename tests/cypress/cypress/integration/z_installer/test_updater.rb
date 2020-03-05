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
    @installer.backup_templates
  end

  after :all do
    @installer.restore_templates
    @installer.disable_installer
    @installer.delete_database_config
  end

  it 'appears when using a database.php file' do
    @page.load
    @page.should have(0).inline_errors
    @page.header.text.should match /ExpressionEngine from \d+\.\d+\.\d+ to \d+\.\d+\.\d+/
  end

  it 'shows an error when no database information exists at all' do
    @installer.delete_database_config
    @page.load
    @page.header.text.should == 'Install Failed'
    @page.error.text.should include 'Unable to locate any database connection information.'
  end

  context 'when updating from 2.x to 3.x' do
    it 'updates using mysql as the dbdriver' do
      @installer.replace_database_config(@database, dbdriver: 'mysql')
      test_update
      test_templates
    end

    it 'updates using localhost as the database host' do
      @installer.replace_database_config(@database, hostname: 'localhost')
      test_update
      test_templates
    end

    it 'updates using 127.0.0.1 as the database host' do
      @installer.replace_database_config(@database, hostname: '127.0.0.1')
      test_update
      test_templates
    end

    it 'updates with the old tmpl_file_basepath' do
      @installer.revert_config
      @installer.replace_config(
        @config,
        tmpl_file_basepath: '../system/expressionengine/templates',
        app_version: '2.20.0'
      )
      test_update
      test_templates
    end

    it 'updates with invalid tmpl_file_basepath' do
      @installer.revert_config
      @installer.replace_config(
        @config,
        tmpl_file_basepath: '../system/not/a/directory/templates',
        app_version: '2.20.0'
      )
      test_update
      test_templates
    end

    it 'updates using new template basepath' do
      @installer.revert_config
      @installer.replace_config(
        @config,
        tmpl_file_basepath: '../system/user/templates',
        app_version: '2.20.0'
      )
      test_update
      test_templates
    end

    it 'has all required modules installed after the update' do
      test_update
      test_templates

      installed_modules = []
      $db.query('SELECT module_name FROM exp_modules').each do |row|
        installed_modules << row['module_name'].downcase
      end

      installed_modules.should include('channel')
      installed_modules.should include('comment')
      installed_modules.should include('member')
      installed_modules.should include('stats')
      installed_modules.should include('rte')
      installed_modules.should include('file')
      installed_modules.should include('filepicker')
      installed_modules.should include('search')
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
    @installer.replace_config(
      File.expand_path('../circleci/config-2.1.3.php'),
      app_version: '213'
    )
    @installer.revert_database_config
    @installer.replace_database_config(
      File.expand_path('../circleci/database-2.1.3.php')
    )

    clean_db do
      $db.query(IO.read('sql/database_2.1.3.sql'))
      clear_db_result
    end

    test_update
  end

  it 'updates a core installation successfully and installs the member module' do
    @installer.revert_config
    @installer.replace_config(
      File.expand_path('../circleci/config-3.0.5-core.php'),
      database: {
        hostname: $test_config[:db_host],
        database: $test_config[:db_name],
        username: $test_config[:db_username],
        password: $test_config[:db_password]
      },
      app_version: '3.0.5'
    )

    clean_db do
      $db.query(IO.read('sql/database_3.0.5-core.sql'))
      clear_db_result
    end

    test_update

    $db.query('SELECT count(*) AS count FROM exp_modules WHERE module_name = "Member"').each do |row|
      row['count'].should == 1
    end
  end

  def test_update(mailinglist = false)
    # Delete any stored mailing lists
    mailing_list_zip = File.expand_path(
      '../../system/user/cache/mailing_list.zip'
    )
    File.delete(mailing_list_zip) if File.exist?(mailing_list_zip)

    # Attempt to work around potential asynchronicity
    sleep 1
    @page.load

    # Wait a second and try loading the page again in case we're not seeing the
    # correct page
    attempts = 0
    header_step_1 = /ExpressionEngine to \d+\.\d+\.\d+/
    while @page.header.text.match(header_step_1) == false && attempts < 5
      sleep 1
      @page.load
      attempts += 1
    end

    @page.should have(0).inline_errors
    @page.header.text.should match /ExpressionEngine from \d+\.\d+\.\d+ to \d+\.\d+\.\d+/
    @page.submit.click
    no_php_js_errors

    @page.header.text.should match /ExpressionEngine to \d+\.\d+\.\d+/
    @page.updater_steps.text.should include 'Running'

    # Sleep until ready
    while (@page.has_updater_steps? && (@page.updater_steps.text.include? 'Running'))
      no_php_js_errors
      sleep 1
    end

    @page.header.text.should == 'Update Complete!'

    @page.has_success_actions?.should == true
    @page.success_actions[0].text.should == 'Log In'

    if mailinglist == false
      @page.should have(1).success_actions
    else
      @page.should have(2).success_actions
      @page.success_actions[1].text.should == 'Download Mailing List'
      File.exist?(mailing_list_zip).should == true
    end

    test_version
  end

  def test_version
    File.open(File.expand_path('../../system/user/config/config.php'), 'r') do |file|
      config_version = file.read.match(/\$config\['app_version'\]\s+=\s+["'](.*?)["'];/)[1]

      File.open(File.expand_path('../../system/ee/installer/controllers/wizard.php'), 'r') do |file|
        wizard_version = file.read.match(/public \$version\s+=\s+["'](.*?)["'];/)[1]

        # @TODO UD files don't account for -dp.#, so just compare the first three segs
        conf = config_version.split(/[\.\-]/)
        wiz = wizard_version.split(/[\.\-]/)

        conf[0].should == wiz[0]
        conf[1].should == wiz[1]
        conf[2].should == wiz[2]
      end
    end
  end

  def test_templates
    File.exist?('../../system/user/templates/default_site/').should == true

    # Ensure none of the templates say anything about Directory access being
    # forbidden
    Dir.glob('../../system/user/templates/default_site/**/*.html') do |filename|
      File.open(filename, 'r') do |file|
        file.each { |l| l.should_not include 'Directory access is forbidden.' }
      end
    end
  end
end
