require './bootstrap.rb'

context('One-Click Updater', () => {

  beforeEach(function() {
    skip 'Need to figure out how to automate build creation again under open source', () => {
    }

    Capybara.default_max_wait_time = 50
    system = '../../system/'
    @config_path = File.expand_path('user/config/config.php', system)
    @syspath = File.expand_path('ee/', system);
    @themespath = File.expand_path('../../themes/ee/');

    // Set to 3.3.4, 3.4.0 is the earliest update file compatible with the new updater
    swap(
      @config_path,
      /\$config\['app_version'\]\s+=\s+.*?;/,
      "$config['app_version'] = '3.3.4';"
    )

    cy.auth();
    page = ControlPanelPage.new
  }

  afterEach(function() {
    // Expand stack trace if we have one
    click_link('view stack trace') unless page.has_no_css?('a[rel="updater-stack-trace"]')
  }

  it('should fail preflight check when permissions are incorrect', () => {
    page.find('.app-about__version').click()
    page.find('.app-about-info__status--update .button').click()

    page.get('wrap').contains('Update Stopped'
    page.get('wrap').contains('The following paths are not writable:'
  }

  it('should continue update when permissions are fixed', () => {
    page.find('.app-about__version').click()
    page.find('.app-about-info__status--update .button').click()

    page.get('wrap').contains('Update Stopped'

    File.chmod(0777, @syspath)
    FileUtils.chmod(0777, Dir.glob(@syspath+'/*'))
    File.chmod(0777, @themespath)
    FileUtils.chmod(0777, Dir.glob(@themespath+'/*'))

    click_link 'Continue'

    page.get('wrap').contains('Up to date!'
  }

  it('should update if there are no impediments', () => {
    page.find('.app-about__version').click()
    page.find('.app-about-info__status--update .button').click()

    page.get('wrap').contains('Up to date!'
  }

}
