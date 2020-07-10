require './bootstrap.rb'

context('File Manager / Upload File', () => {

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
    page.manager_title.invoke('text').then((text) => { expect(text).to.be.equal('File Manager'
    page.should have_title_toolbar
    page.should have_download_all

    // Check that we have a sidebar
    page.should have_sidebar
    page.upload_directories_header.contains('Upload Directories'
    page.should have_new_directory_button
    page.watermarks_header.contains('Watermarks'
    page.should have_new_watermark_button
    page.sidebar.find('li.act').invoke('text').then((text) => { expect(text).to.be.equal('Main Upload Directory'

    page.should_not have_breadcrumb
    page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('File Upload'

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
    page.title_input.invoke('val').then((val) => { expect(val).to.be.equal(''
    page.description_input.invoke('val').then((val) => { expect(val).to.be.equal(''
    page.credit_input.invoke('val').then((val) => { expect(val).to.be.equal(''
    page.location_input.invoke('val').then((val) => { expect(val).to.be.equal(''
  }

  it('requires that a file be uploaded', () => {
    page.form_submit_button.click()
    cy.hasNoErrors()

    page.get('alert').should('be.visible')
    page.get('alert_error').should('be.visible')
    page.get('alert').contains("Cannot Upload File"
    page.get('alert').contains("You did not select a file to upload."
  }

  it('can upload a Markdown file', () => {
    page.attach_file('file', @md_file)
    page.form_submit_button.click()
    cy.hasNoErrors()

    @return.displayed?
    @return.should have_alert
    @return.should have_alert_success
    @return.alert.contains("File Upload Success"
    @return.alert.contains("The file README.md was uploaded successfully."
    @return.should have_selected_file
    @return.selected_file.contains("README.md"
  }

  it('can upload a Markdown file and set the title', () => {
    page.attach_file('file', @md_file)
    page.title_input.clear().type("RSpec README"
    page.form_submit_button.click()
    cy.hasNoErrors()

    @return.displayed?
    @return.should have_alert
    @return.should have_alert_success
    @return.alert.contains("File Upload Success"
    @return.alert.contains("The file RSpec README was uploaded successfully."
    @return.should have_selected_file
    @return.selected_file.contains("README.md"
    @return.selected_file.contains("RSpec README"
  }

  it('can upload a Markdown file and set the description', () => {
    page.attach_file('file', @md_file)
    page.description_input.clear().type("RSpec README"
    page.form_submit_button.click()
    cy.hasNoErrors()

    @return.displayed?
    @return.should have_alert
    @return.should have_alert_success
    @return.alert.contains("File Upload Success"
    @return.alert.contains("The file README.md was uploaded successfully."
    @return.should have_selected_file
    @return.selected_file.contains("README.md"

    @return.selected_file.find('li.edit a').click()()
    cy.hasNoErrors()

    @file.displayed?
    @file.description_inputinvoke('val').then((val) => { expect(val).to.be.equal("RSpec README"
  }

  it('can upload a Markdown file and set the credit', () => {
    page.attach_file('file', @md_file)
    page.credit_input.clear().type("RSpec README"
    page.form_submit_button.click()
    cy.hasNoErrors()

    @return.displayed?
    @return.should have_alert
    @return.should have_alert_success
    @return.alert.contains("File Upload Success"
    @return.alert.contains("The file README.md was uploaded successfully."
    @return.should have_selected_file
    @return.selected_file.contains("README.md"

    @return.selected_file.find('li.edit a').click()()
    cy.hasNoErrors()

    @file.displayed?
    @file.credit_inputinvoke('val').then((val) => { expect(val).to.be.equal("RSpec README"
  }

  it('can upload a Markdown file and set the location', () => {
    page.attach_file('file', @md_file)
    page.location_input.clear().type("RSpec README"
    page.form_submit_button.click()
    cy.hasNoErrors()

    @return.displayed?
    @return.should have_alert
    @return.should have_alert_success
    @return.alert.contains("File Upload Success"
    @return.alert.contains("The file README.md was uploaded successfully."
    @return.should have_selected_file
    @return.selected_file.contains("README.md"

    @return.selected_file.find('li.edit a').click()()
    cy.hasNoErrors()

    @file.displayed?
    @file.location_inputinvoke('val').then((val) => { expect(val).to.be.equal("RSpec README"
  }

  it('cannot upload a shell script', () => {
    page.attach_file('file', @script_file)
    page.form_submit_button.click()
    cy.hasNoErrors()

    page.get('alert').should('be.visible')
    page.get('alert_error').should('be.visible')
    page.get('alert').contains("Cannot Upload File"
    page.get('alert').contains("File not allowed."
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
    @return.alert.contains("File Upload Success"
    @return.alert.contains("The file programming.gif was uploaded successfully."
    @return.should have_selected_file
    @return.selected_file.contains("programming.gif"

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

    page.get('alert').should('be.visible')
    page.get('alert_error').should('be.visible')
    page.get('alert').contains("Cannot Upload File"
    page.get('alert').contains("File not allowed."
  }

  it('cannot upload a PHP script masquerading as an image', () => {
    click_link 'Upload File'
    within '.section-header__controls .filter-submenu', () => {
      click_link 'About'
    }

    page.attach_file('file', @php_file)
    page.form_submit_button.click()
    cy.hasNoErrors()

    page.get('alert').should('be.visible')
    page.get('alert_error').should('be.visible')
    page.get('alert').contains("Cannot Upload File"
    page.get('alert').contains("File not allowed."
  }

  it('shows an error if the directory upload path has no write permissions', () => {
    File.chmod(0555, @upload_dir)
    page.load()
    cy.hasNoErrors()

    page.get('alert').should('be.visible')
    page.get('alert_error').should('be.visible')
    page.get('alert').contains("Directory Not Writable"
    page.get('alert').contains("Cannot write to the directory"
    page.get('alert').contains("Check your file permissions on the server"
    File.chmod(0777, @upload_dir)
  }

  it('shows an error if the directory upload path does not exist', () => {
    File.rename(@upload_dir, @upload_dir + '.rspec')
    page.load()
    cy.hasNoErrors()

    page.contains("404"

    // page.get('alert').should('be.visible')
    // page.get('alert_error').should('be.visible')
    // page.get('alert').contains("Cannot find the directory"
    File.rename(@upload_dir + '.rspec', @upload_dir)
  }

}
