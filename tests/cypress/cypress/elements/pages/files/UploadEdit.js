import FileManagerSection from '../_sections/FileManagerSection'

class UploadEdit extends FileManagerSection {
  constructor() {
      super()
      this.urlMatch = /files\/file\/edit/;

      this.elements({
        'name': 'input[type!=hidden][name=name]',
        'url': 'input[type!=hidden][name="_for_adapter[local][url]"]',
        'adapter': 'div[data-input-value=adapter]',
        'server_path': 'input[type!=hidden][name="_for_adapter[local][server_path]"]',
        'allowed_types': 'div[data-input-value="allowed_types"]',
        'max_size': 'input[type!=hidden][name=max_size]',
        'max_width': 'input[type!=hidden][name=max_width]',
        'max_height': 'input[type!=hidden][name=max_height]',
        'image_manipulations': '#image_manipulations',
        'grid_rows': '#image_manipulations tr:visible',
        'upload_member_groups': 'input[type!=hidden][name="upload_roles[]"]',
        'cat_group': 'input[type!=hidden][name="cat_group[]"]'
      })
    }
    load() {
      cy.contains('Files').click()
      cy.get('div.sidebar h2').contains('New').click()
    }

    load_edit_for_dir(number) {
      cy.contains('Files').click()
      cy.get('div.sidebar .folder-list > div:nth-child('+number.toString()+')').trigger('mouseover')
      cy.get('div.sidebar .folder-list > div:nth-child('+number.toString()+')  a.edit').click({force: true})
    }

    create_manipulation() {

      this.load_edit_for_dir(2)

      this.get('grid_add_no_results').click()
      this.name_for_row(1).type('some_name')
      this.width_for_row(1).type('20')
      this.height_for_row(1).type('30')

      this.get('grid_add').click()
      this.name_for_row(2).type('some_other_name')
      this.resize_type_for_row(2).select('Crop (part of image)')
      this.width_for_row(2).type('50')
      this.height_for_row(2).type('40')

      cy.hasNoErrors()

      this.submit()
      this.get('wrap').contains('Upload directory saved')

      cy.hasNoErrors()
    }

    // Dynamic getter for a specific Grid row
    grid_row(row) {
      // Plus three to skip over header, blank row and no results row
      return this.get('image_manipulations').find('tbody tr:nth-child('+(row+2).toString()+')')
    }

    // Returns the name field in a specific Grid row, and so on...
    name_for_row(row) {
      return this.grid_row(row).find('td:first-child input')
    }

    resize_type_for_row(row) {
      return this.grid_row(row).find('td:nth-child(2) select')
    }

    width_for_row(row) {
      return this.grid_row(row).find('td:nth-child(3) input')
    }

    height_for_row(row) {
      return this.grid_row(row).find('td:nth-child(4) input')
    }

    quality_for_row(row) {
      return this.grid_row(row).find('td:nth-child(5) input')
    }

    watermark_for_row(row) {
      return this.grid_row(row).find('td:nth-child(6) select')
    }

    delete_for_row(row) {
      return this.grid_row(row).find('td:nth-child(7) [rel=remove_row]')
    }
}
export default UploadEdit;
