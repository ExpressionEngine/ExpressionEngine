require './bootstrap.rb'

feature 'File Manager / Edit File' do

  before(:all) do
		# Create backups of these folders so we can restore them after each test
		@upload_dir = File.expand_path('../../images/about/')
		system('mkdir /tmp/about')
		system('cp -r ' + @upload_dir + '/* /tmp/about')
	end

  after(:all) do
    system('rm -rf /tmp/about')
  end

  before(:each) do
    cp_session
    @page = EditFile.new
    @return = FileManager.new
    @page.load
    no_php_js_errors

    @page.displayed?

    # Check that the heder data is intact
    @page.manager_title.text.should eq 'File Manager'
    @page.should have_title_toolbar
    @page.should have_download_all

    # Check that we have a sidebar
    @page.should have_sidebar
    @page.upload_directories_header.text.should include 'Upload Directories'
    @page.should have_new_directory_button
    @page.watermarks_header.text.should include 'Watermarks'
    @page.should have_new_watermark_button

    @page.should have_breadcrumb
    @page.breadcrumb.text.should include "File Manager"
    @page.breadcrumb.text.should include "Meta Data"
    @page.heading.text.should include "Meta Data"
    @page.should have_title_input
    @page.should have_description_input
    @page.should have_credit_input
    @page.should have_location_input
    @page.should have_form_submit_button
  end

  after(:each) do
    system('rm -rf ' + @upload_dir)
    system('mkdir ' + @upload_dir)
    system('cp -r /tmp/about/* ' + @upload_dir)
    FileUtils.chmod_R 0777, @upload_dir
  end

  it 'shows the Edit Meta Data form' do
    @page.breadcrumb.text.should include @page.title_input.value
    @page.heading.text.should include @page.title_input.value
  end

  it 'can edit the title' do
    @page.title_input.set "Rspec was here"
    @page.form_submit_button.click
    no_php_js_errors

    @return.displayed?
    @return.alert.text.should include "The meta data for the file Rspec was here has been updated."
  end

  it 'can edit the description' do
    @page.description_input.set "Rspec was here"
    @page.form_submit_button.click
    no_php_js_errors

    @return.displayed?
    @return.alert.text.should include "The meta data for the file"
    @return.alert.text.should include "has been updated."
  end

  it 'can edit the credit' do
    @page.credit_input.set "Rspec was here"
    @page.form_submit_button.click
    no_php_js_errors

    @return.displayed?
    @return.alert.text.should include "The meta data for the file"
    @return.alert.text.should include "has been updated."
  end

  it 'can edit the location' do
    @page.location_input.set "Rspec was here"
    @page.form_submit_button.click
    no_php_js_errors

    @return.displayed?
    @return.alert.text.should include "The meta data for the file"
    @return.alert.text.should include "has been updated."
  end

  it 'can navigate back to the filemanger' do
    click_link "File Manager"
    no_php_js_errors

    @return.displayed?
  end

end
