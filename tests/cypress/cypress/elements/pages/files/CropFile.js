import FileManagerSection from '../_sections/FileManagerSection'

class CropFile extends FileManagerSection {
  constructor() {
      super()
      this.urlMatch = /files\/file\/crop/;

      this.elements({
        // Main box elements
        'heading': 'div.form-standard form div.form-btns-top h1',
        'crop_tab': 'ul.tabs a[rel="t-0"]',
        'rotate_tab': 'ul.tabs a[rel="t-1"]',
        'resize_tab': 'ul.tabs a[rel="t-2"]',
        'save': 'div.form-standard form div.form-btns-top button',
      
        // Crop Form
        'crop_width_input': 'form div.t-0 input[name="crop_width"]',
        'crop_height_input': 'form div.t-0 input[name="crop_height"]',
        'crop_x_input': 'form div.t-0 input[name="crop_x"]',
        'crop_y_input': 'form div.t-0 input[name="crop_y"]',
        'crop_image_preview': 'form div.t-0 figure.img-preview img',
      
        // Rotate Form
        'rotate_right': 'form div.t-1 input[name="rotate"][value="270"]',
        'rotate_left': 'form div.t-1 input[name="rotate"][value="90"]',
        'flip_vertical': 'form div.t-1 input[name="rotate"][value="vrt"]',
        'flip_horizontal': 'form div.t-1 input[name="rotate"][value="hor"]',
        'rotate_image_preview': 'form div.t-1 figure.img-preview img',
      
        // Resize Form
        'resize_width_input': 'form div.t-2 input[name="resize_width"]',
        'resize_height_input': 'form div.t-2 input[name="resize_height"]',
        'resize_image_preview': 'form div.t-2 figure.img-preview img'
      })
    }
    load() {
      cy.contains('Files').click()
      cy.get('.sidebar').contains('About').click()
      let filename = '';
      cy.get('div.box form div.tbl-wrap table tr:nth-child(2) td:first-child em').invoke('text').then((text) => { 
        filename = text.trim() 		
      })
      cy.get('div.box form div.tbl-wrap table tr:nth-child(2) td:nth-child(4) ul.toolbar li.crop').click()
  
      return filename
    }
}
export default CropFile;
