import FileManagerSection from '../_sections/FileManagerSection'

class UploadFile extends FileManagerSection {
  constructor() {
      super()
      this.urlMatch = /files\/upload/;

      this.elements({
        // Main box elements
        'heading': 'div.form-standard form div.form-btns-top h1',
      
        // Edit form
        'file_input': 'div.col.w-12 div.form-standard form fieldset input[name="file"]',
        'title_input': 'div.col.w-12 div.form-standard form fieldset input[name="title"]',
        'description_input': 'div.col.w-12 div.form-standard form fieldset textarea[name="description"]',
        'credit_input': 'div.col.w-12 div.form-standard form fieldset input[name="credit"]',
        'location_input': 'div.col.w-12 div.form-standard form fieldset input[name="location"]',
        'form_submit_button': 'div.form-standard form div.form-btns-top input[type="submit"]'
      
      })
    }
    load() {
      cy.contains('Files').click()
      cy.contains('Upload File').click()
      cy.get('upload_new_file_filter_menu').contains('Main Upload Directory').click()
    }
}
export default UploadFile;