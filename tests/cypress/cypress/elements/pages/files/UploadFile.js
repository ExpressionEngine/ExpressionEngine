import FileManagerSection from '../_sections/FileManagerSection'

class UploadFile extends FileManagerSection {
  constructor() {
      super()
      this.urlMatch = /files\/upload/;

      this.elements({
        // Main box elements
        'heading': 'div.form-standard form .form-btns h3',

        // Edit form
        'file_input': '.ee-main__content div.form-standard form input[type!=hidden][name="file"]',
        'title_input': '.ee-main__content div.form-standard form input[type!=hidden][name="title"]',
        'description_input': '.ee-main__content div.form-standard form textarea[name="description"]',
        'credit_input': '.ee-main__content div.form-standard form input[type!=hidden][name="credit"]',
        'location_input': '.ee-main__content div.form-standard form input[type!=hidden][name="location"]',
        'form_submit_button': '.ee-main__content div.form-standard form .form-btns-top [type="submit"]'

      })
    }
    load() {
      cy.visit('admin.php?/cp/files')
      cy.get('.button--primary').contains('Upload').click()
      cy.get('.dropdown--open .dropdown__link').contains('Main Upload Directory').click()
    }
}
export default UploadFile;
