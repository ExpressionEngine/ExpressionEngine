import FileManagerSection from '../_sections/FileManagerSection'

class EditFile extends FileManagerSection {
  constructor() {
      super()
      this.urlMatch = /files\/file\/edit/;

      this.elements({
        // Main box elements
        'heading': '.ee-main .title-bar .title-bar__title',
        'crop_button': 'div.form-standard form .tab-bar__right-buttons .form-btns h1 a.action',

        //Modal
        'modal': '.modal-view-file',
        'modal_heading': '.modal-view-file h1',
        'preview_image': '.modal-view-file .file-preview-modal__preview-file',

        // Edit form
        'title_input': '.modal-view-file fieldset input[type!=hidden][name="title"]',
        'description_input': '.modal-view-file fieldset textarea[name="description"]',
        'credit_input': '.modal-view-file fieldset input[type!=hidden][name="credit"]',
        'location_input': '.modal-view-file fieldset input[type!=hidden][name="location"]',
        'form_submit_button': '.modal-view-file .form-btns button[type="submit"]'
      })
    }
    load() {
      cy.visit('admin.php?/cp/files')
    }

    submit(fileName, fileType, selector){
        cy.get(selector).then(subject => {
                cy.fixture(fileName, 'base64')
                .then(Cypress.Blob.base64StringToBlob)
                .then(blob => {
                    const el = subject[0]
                    const testFile = new File([blob], fileName, { type: fileType })
                    const dataTransfer = new DataTransfer()
                    dataTransfer.items.add(testFile)
                    el.files = dataTransfer.files
                    console.log(el.files)
              })
        })
    }
}
export default EditFile;
