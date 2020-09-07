require './bootstrap.rb'

context('Installer', () => {
  before :all do
    @installer = Installer::Prepare.new
    @installer.enable_installer
    @installer.replace_config
  }

  beforeEach(function(){
    // Delete existing config and create a new one
    @config = File.expand_path('../../system/user/config/config.php')
    @env = File.expand_path('../../.env.php')
    File.delete(@config) if File.exist?(@config)
    File.new(@config, 'w')
    FileUtils.chmod(0666, @config)

    // print @env
    // print File.read(@env)
    // print @config
    // print File.read(@config)

    page = Installer::Base.new
    page.load()
    cy.hasNoErrors()
  }

  after :all do
    @installer.disable_installer
    @installer.revert_config
  }

  it('loads', () => {
    page.should have(0).inline_errors
    page.install_form.all_there?.should == true
  }

  context('when installing', () => {
    it('installs successfully using 127.0.0.1 as the database host', () => {
      page.install_form.db_hostname.clear().type('127.0.0.1'
      page.install_form.db_name.set $test_config[:db_name]
      page.install_form.db_username.set $test_config[:db_username]
      page.install_form.db_password.set $test_config[:db_password]
      page.install_form.username.clear().type('admin'
      page.install_form.email_address.clear().type('hello@expressionengine.com'
      page.install_form.password.clear().type('password'
      page.install_form.license_agreement.click()
      page.install_form.install_submit.click()

      cy.hasNoErrors()
      page.header.invoke('text').then((text) => { expect(text).to.be.equal('Install Complete!'
      page.install_success.updater_msg.text.should match /ExpressionEngine has been installed/
      page.install_success.all_there?.should == true
    }

    it('installs successfully using localhost as the database host', () => {
      page.install_form.db_hostname.clear().type('localhost'
      page.install_form.db_name.set $test_config[:db_name]
      page.install_form.db_username.set $test_config[:db_username]
      page.install_form.db_password.set $test_config[:db_password]
      page.install_form.username.clear().type('admin'
      page.install_form.email_address.clear().type('hello@expressionengine.com'
      page.install_form.password.clear().type('password'
      page.install_form.license_agreement.click()
      page.install_form.install_submit.click()

      cy.hasNoErrors()
      page.header.invoke('text').then((text) => { expect(text).to.be.equal('Install Complete!'
      page.install_success.updater_msg.text.should match /ExpressionEngine has been installed/
      page.install_success.all_there?.should == true
    }

    it('installs successfully with the default theme', () => {
      @installer.backup_templates

      page.install_form.db_hostname.clear().type('localhost'
      page.install_form.db_name.set $test_config[:db_name]
      page.install_form.db_username.set $test_config[:db_username]
      page.install_form.db_password.set $test_config[:db_password]

      page.install_form.install_default_theme.click()
      page.install_form.username.clear().type('admin'
      page.install_form.email_address.clear().type('hello@expressionengine.com'
      page.install_form.password.clear().type('password'
      page.install_form.license_agreement.click()
      page.install_form.install_submit.click()

      cy.hasNoErrors()
      page.header.invoke('text').then((text) => { expect(text).to.be.equal('Install Complete!'
      page.install_success.updater_msg.text.should match /ExpressionEngine has been installed/
      page.install_success.all_there?.should == true

      @installer.restore_templates
    }

    it('has all require modules installed after installation', () => {
      page.install_form.db_hostname.clear().type('127.0.0.1'
      page.install_form.db_name.set $test_config[:db_name]
      page.install_form.db_username.set $test_config[:db_username]
      page.install_form.db_password.set $test_config[:db_password]
      page.install_form.username.clear().type('admin'
      page.install_form.email_address.clear().type('hello@expressionengine.com'
      page.install_form.password.clear().type('password'
      page.install_form.license_agreement.click()
      page.install_form.install_submit.click()

      cy.hasNoErrors()
      page.header.invoke('text').then((text) => { expect(text).to.be.equal('Install Complete!'
      page.install_success.updater_msg.text.should match /ExpressionEngine has been installed/
      page.install_success.all_there?.should == true

      installed_modules = []
      $db.query('SELECT module_name FROM exp_modules').each do |row|
        installed_modules << row['module_name'].downcase
      }

      installed_modules.should include('channel')
      installed_modules.should include('comment')
      installed_modules.should include('member')
      installed_modules.should include('stats')
      installed_modules.should include('rte')
      installed_modules.should include('file')
      installed_modules.should include('filepicker')
      installed_modules.should include('search')
    }

    it('uses {base_url} and {base_path}', () => {
      page.install_form.db_hostname.clear().type('127.0.0.1'
      page.install_form.db_name.set $test_config[:db_name]
      page.install_form.db_username.set $test_config[:db_username]
      page.install_form.db_password.set $test_config[:db_password]
      page.install_form.username.clear().type('admin'
      page.install_form.email_address.clear().type('hello@expressionengine.com'
      page.install_form.password.clear().type('password'
      page.install_form.license_agreement.click()
      page.install_form.install_submit.click()

      cy.hasNoErrors()
      @installer.disable_installer

    //   print @env
    //   print File.read(@env)
      File.rename '../../system/ee/installer', '../../system/ee/installer_old'
    //   print @config
    //   print File.read(@config)
      page.install_success.login_button.click()
      cy.auth();

      @settings = UrlsSettings.new
      @settings.load

      @settings.base_url.invoke('val').then((val) => { expect(val).to.be.equal($test_config[:app_host]
      @settings.base_path.value.should_not == ''
      @settings.site_url.value.should include '{base_url}'
      @settings.cp_url.value.should include '{base_url}'
      @settings.theme_folder_url.value.should include '{base_url}'
      @settings.theme_folder_path.value.should include '{base_path}'

      @settings = MessagingSettings.new
      @settings.load

      @settings.prv_msg_upload_url.value.should include '{base_url}'
      @settings.prv_msg_upload_path.value.should include '{base_path}'

      @settings = CaptchaSettings.new
      @settings.load

      @settings.captcha_url.value.should include '{base_url}'
      @settings.captcha_path.value.should include '{base_path}'

      File.rename '../../system/ee/installer_old', '../../system/ee/installer'
      @installer.enable_installer
    }
  }

  context('when using invalid database credentials', () => {
    it('shows an error with no database credentials', () => {
      page.install_form.install_submit.click()

      cy.hasNoErrors()
      page.install_form.all_there?.should == true
      page.inline_errors.should have_at_least(1).items
    }

    it('shows an inline error when using an incorrect database host', () => {
      page.install_form.db_hostname.clear().type('nonsense'
      page.install_form.install_submit.click()

      cy.hasNoErrors()
      page.install_form.all_there?.should == true
      page.inline_errors.should have_at_least(1).items
      page.has_inline_error('The database host you submitted is invalid.') == true
    }

    it('shows an inline error when using an incorrect database name', () => {
      page.install_form.db_hostname.set $test_config[:db_host]
      page.install_form.db_name.clear().type('nonsense'
      page.install_form.install_submit.click()

      cy.hasNoErrors()
      page.install_form.all_there?.should == true
      page.inline_errors.should have_at_least(1).items
      page.has_inline_error('The database name you submitted is invalid.') == true
    }

    it('shows an error when using an incorrect database user', () => {
      page.install_form.db_hostname.set $test_config[:db_host]
      page.install_form.db_name.set $test_config[:db_name]
      page.install_form.db_username.clear().type('nonsense'
      page.install_form.username.clear().type('admin'
      page.install_form.email_address.clear().type('hello@expressionengine.com'
      page.install_form.password.clear().type('password'
      page.install_form.license_agreement.click()
      page.install_form.install_submit.click()

      cy.hasNoErrors()
      page.install_form.all_there?.should == true
      page.inline_errors.should have(0).items
      page.should have_error
      page.error.contains('The database user and password combination you submitted is invalid.'
    }
  }

  context('when using an invalid database prefix', () => {
    it('shows an error when the database prefix is too long', () => {
      page.execute_script("$('input[maxlength=30]').prop('maxlength', 80);")
      page.install_form.db_prefix.clear().type('1234567890123456789012345678901234567890'
      page.install_form.install_submit.click()
      page.inline_errors.should have_at_least(1).items
      page.has_inline_error(/This field cannot exceed \d+ characters in length./) == true
    }

    it('shows an error when using invalid characters in the database prefix', () => {
      page.install_form.db_prefix.clear().type('<nonsense>'
      page.install_form.install_submit.click()
      page.inline_errors.should have_at_least(1).items
      page.has_inline_error('There are invalid characters in the database prefix.') == true
    }

    it('shows an error when using exp_ in the database prefix', () => {
      page.install_form.db_prefix.clear().type('exp_'
      page.install_form.install_submit.click()
      page.inline_errors.should have_at_least(1).items
      page.has_inline_error('The database prefix cannot contain the string "exp_".') == true
    }
  }

  context('when using an invalid username', () => {
    it('shows an error when using invalid characters', () => {
      page.install_form.username.clear().type('non<>sense'
      page.install_form.install_submit.click()
      page.inline_errors.should have_at_least(1).items
      page.has_inline_error('Your username cannot use the following characters:') == true
    }

    it('shows an error when using a too-short username', () => {
      page.install_form.username.clear().type('123'
      page.install_form.install_submit.click()
      page.inline_errors.should have_at_least(1).items
      page.has_inline_error('Your username must be at least 4 characters long') == true
    }

    it('shows an error when using a too-long username', () => {
      page.execute_script("$('input[maxlength=50]').prop('maxlength', 80);")
      page.install_form.username.clear().type('12345678901234567890123456789012345678901234567890123456789012345678901234567890'
      page.install_form.install_submit.click()
      page.inline_errors.should have_at_least(1).items
      page.has_inline_error(/Your username cannot be over \d+ characters in length/) == true
    }
  }

  context('when using an invalid email address', () => {
    it('shows an error when no domain is supplied', () => {
      page.install_form.email_address.clear().type('nonsense'
      page.install_form.install_submit.click()
      page.inline_errors.should have_at_least(1).items
      page.has_inline_error('This field must contain a valid email address') == true
    }

    it('shows an error when no tld is supplied', () => {
      page.install_form.email_address.clear().type('nonsense@example'
      page.install_form.install_submit.click()
      page.inline_errors.should have_at_least(1).items
      page.has_inline_error('This field must contain a valid email address') == true
    }

    it('shows an error when no username is supplied', () => {
      page.install_form.email_address.clear().type('example.com'
      page.install_form.install_submit.click()
      page.inline_errors.should have_at_least(1).items
      page.has_inline_error('This field must contain a valid email address') == true
    }
  }

  context('when using an invalid password', () => {
    it('shows an error when the password is too short', () => {
      page.install_form.password.clear().type('123'
      page.install_form.install_submit.click()
      page.inline_errors.should have_at_least(1).items
      page.has_inline_error(/Your password must be at least \d+ characters long/) == true
    }

    it('shows an error when the password is too long', () => {
      page.execute_script("$('input[maxlength=72]').prop('maxlength', 80);")
      page.install_form.password.clear().type('12345678901234567890123456789012345678901234567890123456789012345678901234567890'
      page.install_form.install_submit.click()
      page.inline_errors.should have_at_least(1).items
      page.has_inline_error(/Your password cannot be over \d+ characters in length/) == true
    }

    it' shows an error when the username and password are the same', () => {
      page.install_form.username.clear().type('nonsense'
      page.install_form.password.clear().type('nonsense'
      page.install_form.install_submit.click()
      page.inline_errors.should have_at_least(1).items
      page.has_inline_error('The password cannot be based on the username') == true
    }
  }

  context('when not agreeing to the license agreement', () => {
    it('will not install without the license agreement checked', () => {
      page.install_form.db_hostname.clear().type('127.0.0.1'
      page.install_form.db_name.set $test_config[:db_name]
      page.install_form.db_username.set $test_config[:db_username]
      page.install_form.db_password.set $test_config[:db_password]
      page.install_form.username.clear().type('admin'
      page.install_form.email_address.clear().type('hello@expressionengine.com'
      page.install_form.password.clear().type('password'
      page.install_form.install_submit.click()

      cy.hasNoErrors()
      page.install_form.all_there?.should == true
      page.install_success.all_there?.should == false
      page.inline_errors.should have_at_least(1).items
      page.has_inline_error('You must accept the terms and conditions of the license agreement.') == true
    }
  }
}
