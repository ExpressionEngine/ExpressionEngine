import FileManagerSection from '../_sections/FileManagerSection'

class CropFile extends FileManagerSection {
  constructor() {
      super()
      this.urlMatch = /files\/file\/crop/;

      this.elements({
        // Main box elements
        'heading': '.ee-main .title-bar .title-bar__title',
        'crop_tab': '.tab-bar__tabs [rel="t-crop"]',
        'rotate_tab': '.tab-bar__tabs [rel="t-rotate"]',
        'resize_tab': '.tab-bar__tabs [rel="t-resize"]',
        'save': '.ee-main__content form .form-btns.form-btns-top button[value="save"]',

        // Crop Form
        'crop_width_input': 'form div.t-crop input[type!=hidden][name="crop_width"]',
        'crop_height_input': 'form div.t-crop input[type!=hidden][name="crop_height"]',
        'crop_x_input': 'form div.t-crop input[type!=hidden][name="crop_x"]',
        'crop_y_input': 'form div.t-crop input[type!=hidden][name="crop_y"]',
        'crop_image_preview': 'form .file-preview-modal__preview img',

        // Rotate Form
        'rotate_right': 'form div.t-rotate input[type!=hidden][name="rotate"][value="270"]',
        'rotate_left': 'form div.t-rotate input[type!=hidden][name="rotate"][value="90"]',
        'flip_vertical': 'form div.t-rotate input[type!=hidden][name="rotate"][value="vrt"]',
        'flip_horizontal': 'form div.t-rotate input[type!=hidden][name="rotate"][value="hor"]',
        'rotate_image_preview': 'form .file-preview-modal__preview img',

        // Resize Form
        'resize_width_input': 'form div.t-resize input[type!=hidden][name="resize_width"]',
        'resize_height_input': 'form div.t-resize input[type!=hidden][name="resize_height"]',
        'resize_image_preview': 'form .file-preview-modal__preview img',
      })
    }
    load() {
      cy.contains('Files').click()
      cy.get('.sidebar__link').contains('About').click()
      let filename = cy.get('.ee-main__content form .table-responsive table tr:nth-child(2) td:first-child em').invoke('text').then((text) => {
        console.log(text.trim())
        return text.trim()
      })
      cy.get('.ee-main__content form .table-responsive table tr:nth-child(2) td:nth-child(4) ul.toolbar li.crop').click()

      console.log(filename)

      return filename
    }
}
export default CropFile;