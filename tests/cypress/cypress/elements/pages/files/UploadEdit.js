import FileManagerSection from '../_sections/FileManagerSection'

class UploadEdit extends FileManagerSection {
  constructor() {
      super()
      this.urlMatch = /files\/file\/edit/;

      this.elements({
        'name': 'input[name=name]',
        'url': 'input[name=url]',
        'server_path': 'input[name=server_path]',
        'allowed_types': 'input[name=allowed_types]',
        'max_size': 'input[name=max_size]',
        'max_width': 'input[name=max_width]',
        'max_height': 'input[name=max_height]',
        'image_manipulations': '#image_manipulations',
        'grid_rows': '#image_manipulations tr:visible',
        'upload_member_groups': 'input[name="upload_member_groups[]"]',
        'cat_group': 'input[name="cat_group[]"]'
      })
    }
    load() {
      cy.contains('Files').click()
      cy.get('div.sidebar h2:nth-child(1)').contains('New').click()
    }

    load_edit_for_dir(number) {
      cy.contains('Files').click()
      cy.get('div.sidebar .folder-list > li:nth-child('+number.toString()+')').trigger('mouseover')
      cy.get('div.sidebar .folder-list > li:nth-child('+number.toString()+')  li.edit a').click()
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
      return this.grid_row(row).find('td:nth-child(7) li.remove a')
    }
}
export default UploadEdit;