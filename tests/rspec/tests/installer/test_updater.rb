require './bootstrap.rb'

feature 'Updater' do
  before :all do
    @installer = Installer::Prepare.new
    @installer.enable_installer
    @installer.disable_rename
  end

  before :each do
    @installer.version = '2.20.0'
    
    @page = Installer::Updater.new
    @page.load
    no_php_js_errors
  end

  after :all do
    @installer.disable_installer
    @installer.enable_rename
  end

  it 'should appear when using a database.php file'
  it 'should upgrade a 2.x installation to 3.0'
end
