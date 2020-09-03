import FileManagerSection from '../_sections/FileManagerSection'

class WatermarkEdit extends FileManagerSection {
  constructor() {
      super()
      this.urlMatch = /files\/file\/edit/;

      this.elements({
        'wm_name': 'input[type!=hidden][name=wm_name]',
        'wm_type': 'input[type!=hidden][name=wm_type]',
        'wm_vrt_alignment': 'input[type!=hidden][name=wm_vrt_alignment]',
        'wm_hor_alignment': 'input[type!=hidden][name=wm_hor_alignment]',
        'wm_padding': 'input[type!=hidden][name=wm_padding]',
        'wm_hor_offset': 'input[type!=hidden][name=wm_hor_offset]',
        'wm_vrt_offset': 'input[type!=hidden][name=wm_vrt_offset]',

        // Text options
        'wm_use_font': '.toggle-btn[data-toggle-for="wm_use_font"]',
        'wm_text': 'input[type!=hidden][name=wm_text]',
        'wm_font': 'input[type!=hidden][name=wm_font]',
        'wm_font_size': 'input[type!=hidden][name=wm_font_size]',
        'wm_font_color': 'input[type!=hidden][name=wm_font_color]',
        'wm_use_drop_shadow': '.toggle-btn[data-toggle-for="wm_use_drop_shadow"]',
        'wm_shadow_distance': 'input[type!=hidden][name=wm_shadow_distance]',
        'wm_shadow_color': 'input[type!=hidden][name=wm_shadow_color]',

        // Image options
        'wm_image_path': 'input[type!=hidden][name=wm_image_path]',
        'wm_opacity': 'input[type!=hidden][name=wm_opacity]',
        'wm_x_transp': 'input[type!=hidden][name=wm_x_transp]',
        'wm_y_transp': 'input[type!=hidden][name=wm_y_transp]'
      })
    }
    load() {
      cy.contains('Files').click()
      cy.get('div.sidebar a').contains('Watermarks').click()
      cy.get('a').contains('Create New').click()
    }

    load_edit_for_watermark(number) {
      cy.contains('Watermarks').click()

      cy.get('.wrap tbody tr:nth-child('+number.toString()+') li.edit a').click()
    }
}
export default WatermarkEdit;
