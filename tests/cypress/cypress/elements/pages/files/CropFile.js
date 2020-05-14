import FileManagerSection from '../_sections/FileManagerSection'

class CropFile extends FileManagerSection {
  constructor() {
      super()
      this.urlMatch = /files\/file\/crop/;

      this.elements({
        // Main box elements
        'heading': '.ee-main .title-bar .title-bar__title',
        'crop_tab': '.tab-bar__tabs [rel="t-0"]',
        'rotate_tab': '.tab-bar__tabs [rel="t-1"]',
        'resize_tab': '.tab-bar__tabs [rel="t-2"]',
        'save': '.ee-main__content form .form-btns.form-btns-top button',

        // Crop Form
        'crop_width_input': 'form div.t-0 input[type!=hidden][name="crop_width"]',
        'crop_height_input': 'form div.t-0 input[type!=hidden][name="crop_height"]',
        'crop_x_input': 'form div.t-0 input[type!=hidden][name="crop_x"]',
        'crop_y_input': 'form div.t-0 input[type!=hidden][name="crop_y"]',
        'crop_image_preview': 'form div.t-0 figure.img-preview img',

        // Rotate Form
        'rotate_right': 'form div.t-1 input[type!=hidden][name="rotate"][value="270"]',
        'rotate_left': 'form div.t-1 input[type!=hidden][name="rotate"][value="90"]',
        'flip_vertical': 'form div.t-1 input[type!=hidden][name="rotate"][value="vrt"]',
        'flip_horizontal': 'form div.t-1 input[type!=hidden][name="rotate"][value="hor"]',
        'rotate_image_preview': 'form div.t-1 figure.img-preview img',

        // Resize Form
        'resize_width_input': 'form div.t-2 input[type!=hidden][name="resize_width"]',
        'resize_height_input': 'form div.t-2 input[type!=hidden][name="resize_height"]',
        'resize_image_preview': 'form div.t-2 figure.img-preview img'
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