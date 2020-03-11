import FileManagerSection from '../_sections/FileManagerSection'

class EditFile extends FileManagerSection {
  constructor() {
      super()
      this.urlMatch = /files\/file\/edit/;

      this.elements({
        // Main box elements
        'heading': 'div.form-standard form div.form-btns-top h1',
        'crop_button': 'div.form-standard form div.form-btns-top h1 a.action',
      
        // Edit form
        'title_input': 'fieldset input[name="title"]',
        'description_input': 'fieldset textarea[name="description"]',
        'credit_input': 'fieldset input[name="credit"]',
        'location_input': 'fieldset input[name="location"]',
        'form_submit_button': '.form-btns-top input[type="submit"]'
      })
    }
    load() {
      cy.contains('Files').click()
      cy.get('div.box form div.tbl-wrap table tr:nth-child(2) td:nth-child(4) ul.toolbar li.edit').click()
    }
}
export default EditFile;
