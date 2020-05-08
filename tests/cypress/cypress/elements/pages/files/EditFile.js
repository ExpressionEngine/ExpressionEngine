import FileManagerSection from '../_sections/FileManagerSection'

class EditFile extends FileManagerSection {
  constructor() {
      super()
      this.urlMatch = /files\/file\/edit/;

      this.elements({
        // Main box elements
        'heading': 'div.form-standard form .tab-bar__right-buttons .form-btns h1',
        'crop_button': 'div.form-standard form .tab-bar__right-buttons .form-btns h1 a.action',

        //Modal
        'modal': '.modal-view-file',
        'modal_heading': '.modal-view-file h1',
        'preview_image': '.modal-view-file .file-preview-modal__preview-file',

        // Edit form
        'title_input': '.modal-view-file fieldset input[name="title"]',
        'description_input': '.modal-view-file fieldset textarea[name="description"]',
        'credit_input': '.modal-view-file fieldset input[name="credit"]',
        'location_input': '.modal-view-file fieldset input[name="location"]',
        'form_submit_button': '.modal-view-file .form-btns button[type="submit"]'
      })
    }
    load() {
      cy.contains('Files').click()
      cy.get('.ee-main__content form .table-responsive table tr:nth-child(2) td:nth-child(4) ul.toolbar li.edit').click()
    }
}
export default EditFile;
