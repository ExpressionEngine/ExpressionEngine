require './bootstrap.rb'

feature 'File Manager / Upload File', () => {

  beforeEach(function() {
    @md_file = File.expand_path('support/file/README.md')
    @script_file = File.expand_path('support/file/script.sh')
    @image_file = File.expand_path('support/file/programming.gif')
    @php_file = File.expand_path('support/file/clever.php.png')

    @upload_dir = File.expand_path('../../images/uploads')

    cy.auth();
    page = UploadFile.new
    @return = FileManager.new
    @file = EditFile.new
    page.load()
    cy.hasNoErrors()

    page.displayed?

    // Check that the heder data is intact
    page.manager_title.text.should eq 'File Manager'
    page.should have_title_toolbar
    page.should have_download_all

    // Check that we have a sidebar
    page.should have_sidebar
    page.upload_directories_header.text.should include 'Upload Directories'
    page.should have_new_directory_button
    page.watermarks_header.text.should include 'Watermarks'
    page.should have_new_watermark_button
    page.sidebar.find('li.act').text.should eq 'Main Upload Directory'

    page.should_not have_breadcrumb
    page.heading.text.should eq 'File Upload'

    page.should have_file_input
    page.should have_title_input
    page.should have_description_input
    page.should have_credit_input
    page.should have_location_input
    page.should have_form_submit_button
  }

  // Restore the images/uploads directory
  afterEach(function() {
    Dir.foreach(@upload_dir) {|f|
      fn = File.join(@upload_dir, f)
      File.delete(fn) if f != '.' && f != '..' && f != 'index.html' && f != '_thumbs'
    }
  }

  it('shows the upload form', () => {
    page.title_input.value.should eq ''
    page.description_input.value.should eq ''
    page.credit_input.value.should eq ''
    page.location_input.value.should eq ''
  }

  it('requires that a file be uploaded', () => {
    page.form_submit_button.click()
    cy.hasNoErrors()

    page.should have_alert
    page.should have_alert_error
    page.alert.text.should include "Cannot Upload File"
    page.alert.text.should include "You did not select a file to upload."
  }

  it('can upload a Markdown file', () => {
    page.attach_file('file', @md_file)
    page.form_submit_button.click()
    cy.hasNoErrors()

    @return.displayed?
    @return.should have_alert
    @return.should have_alert_success
    @return.alert.text.should include "File Upload Success"
    @return.alert.text.should include "The file README.md was uploaded successfully."
    @return.should have_selected_file
    @return.selected_file.text.should include "README.md"
  }

  it('can upload a Markdown file and set the title', () => {
    page.attach_file('file', @md_file)
    page.title_input.set "RSpec README"
    page.form_submit_button.click()
    cy.hasNoErrors()

    @return.displayed?
    @return.should have_alert
    @return.should have_alert_success
    @return.alert.text.should include "File Upload Success"
    @return.alert.text.should include "The file RSpec README was uploaded successfully."
    @return.should have_selected_file
    @return.selected_file.text.should include "README.md"
    @return.selected_file.text.should include "RSpec README"
  }

  it('can upload a Markdown file and set the description', () => {
    page.attach_file('file', @md_file)
    page.description_input.set "RSpec README"
    page.form_submit_button.click()
    cy.hasNoErrors()

    @return.displayed?
    @return.should have_alert
    @return.should have_alert_success
    @return.alert.text.should include "File Upload Success"
    @return.alert.text.should include "The file README.md was uploaded successfully."
    @return.should have_selected_file
    @return.selected_file.text.should include "README.md"

    @return.selected_file.find('li.edit a').click()()
    cy.hasNoErrors()

    @file.displayed?
    @file.description_input.value.should eq "RSpec README"
  }

  it('can upload a Markdown file and set the credit', () => {
    page.attach_file('file', @md_file)
    page.credit_input.set "RSpec README"
    page.form_submit_button.click()
    cy.hasNoErrors()

    @return.displayed?
    @return.should have_alert
    @return.should have_alert_success
    @return.alert.text.should include "File Upload Success"
    @return.alert.text.should include "The file README.md was uploaded successfully."
    @return.should have_selected_file
    @return.selected_file.text.should include "README.md"

    @return.selected_file.find('li.edit a').click()()
    cy.hasNoErrors()

    @file.displayed?
    @file.credit_input.value.should eq "RSpec README"
  }

  it('can upload a Markdown file and set the location', () => {
    page.attach_file('file', @md_file)
    page.location_input.set "RSpec README"
    page.form_submit_button.click()
    cy.hasNoErrors()

    @return.displayed?
    @return.should have_alert
    @return.should have_alert_success
    @return.alert.text.should include "File Upload Success"
    @return.alert.text.should include "The file README.md was uploaded successfully."
    @return.should have_selected_file
    @return.selected_file.text.should include "README.md"

    @return.selected_file.find('li.edit a').click()()
    cy.hasNoErrors()

    @file.displayed?
    @file.location_input.value.should eq "RSpec README"
  }

  it('cannot upload a shell script', () => {
    page.attach_file('file', @script_file)
    page.form_submit_button.click()
    cy.hasNoErrors()

    page.should have_alert
    page.should have_alert_error
    page.alert.text.should include "Cannot Upload File"
    page.alert.text.should include "File not allowed."
  }

  it('can upload a image when the directory is restricted to images', () => {
    click_link 'Upload File'
    within '.section-header__controls .filter-submenu', () => {
      click_link 'About'
    }

    page.attach_file('file', @image_file)
    page.form_submit_button.click()
    cy.hasNoErrors()

    @return.displayed?
    @return.should have_alert
    @return.should have_alert_success
    @return.alert.text.should include "File Upload Success"
    @return.alert.text.should include "The file programming.gif was uploaded successfully."
    @return.should have_selected_file
    @return.selected_file.text.should include "programming.gif"

    // Cleaning up after myself
    File.delete(File.expand_path('../../images/about/programming.gif'))
    File.delete(File.expand_path('../../images/about/_thumbs/programming.gif'))
  }

  it('cannot upload a non-image when the directory is restricted to images', () => {
    click_link 'Upload File'
    within '.section-header__controls .filter-submenu', () => {
      click_link 'About'
    }

    page.attach_file('file', @md_file)
    page.form_submit_button.click()
    cy.hasNoErrors()

    page.should have_alert
    page.should have_alert_error
    page.alert.text.should include "Cannot Upload File"
    page.alert.text.should include "File not allowed."
  }

  it('cannot upload a PHP script masquerading as an image', () => {
    click_link 'Upload File'
    within '.section-header__controls .filter-submenu', () => {
      click_link 'About'
    }

    page.attach_file('file', @php_file)
    page.form_submit_button.click()
    cy.hasNoErrors()

    page.should have_alert
    page.should have_alert_error
    page.alert.text.should include "Cannot Upload File"
    page.alert.text.should include "File not allowed."
  }

  it('shows an error if the directory upload path has no write permissions', () => {
    File.chmod(0555, @upload_dir)
    page.load()
    cy.hasNoErrors()

    page.should have_alert
    page.should have_alert_error
    page.alert.text.should include "Directory Not Writable"
    page.alert.text.should include "Cannot write to the directory"
    page.alert.text.should include "Check your file permissions on the server"
    File.chmod(0777, @upload_dir)
  }

  it('shows an error if the directory upload path does not exist', () => {
    File.rename(@upload_dir, @upload_dir + '.rspec')
    page.load()
    cy.hasNoErrors()

    page.text.should include "404"

    // page.should have_alert
    // page.should have_alert_error
    // page.alert.text.should include "Cannot find the directory"
    File.rename(@upload_dir + '.rspec', @upload_dir)
  }

}
