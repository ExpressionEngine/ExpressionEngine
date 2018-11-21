require './bootstrap.rb'

feature 'One-Click Updater' do

  before(:each) do
    skip 'Need to figure out how to automate build creation again under open source' do
    end

    Capybara.default_max_wait_time = 50
    system = '../../system/'
    @config_path = File.expand_path('user/config/config.php', system)
    @syspath = File.expand_path('ee/', system);
    @themespath = File.expand_path('../../themes/ee/');

    # Set to 3.3.4, 3.4.0 is the earliest update file compatible with the new updater
    swap(
      @config_path,
      /\$config\['app_version'\]\s+=\s+.*?;/,
      "$config['app_version'] = '3.3.4';"
    )

    cp_session
    @page = ControlPanelPage.new
  end

  after(:each) do
    # Expand stack trace if we have one
    click_link('view stack trace') unless page.has_no_css?('a[rel="updater-stack-trace"]')
  end

  it 'should fail preflight check when permissions are incorrect' do
    @page.find('.app-about__version').click
    @page.find('.app-about-info__status--update .button').click

    @page.should have_text 'Update Stopped'
    @page.should have_text 'The following paths are not writable:'
  end

  it 'should continue update when permissions are fixed' do
    @page.find('.app-about__version').click
    @page.find('.app-about-info__status--update .button').click

    @page.should have_text 'Update Stopped'

    File.chmod(0777, @syspath)
    FileUtils.chmod(0777, Dir.glob(@syspath+'/*'))
    File.chmod(0777, @themespath)
    FileUtils.chmod(0777, Dir.glob(@themespath+'/*'))

    click_link 'Continue'

    @page.should have_text 'Up to date!'
  end

  it 'should update if there are no impediments' do
    @page.find('.app-about__version').click
    @page.find('.app-about-info__status--update .button').click

    @page.should have_text 'Up to date!'
  end

end
