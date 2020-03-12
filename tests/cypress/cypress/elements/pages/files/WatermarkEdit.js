import FileManagerSection from '../_sections/FileManagerSection'

class WatermarkEdit extends FileManagerSection {
  constructor() {
      super()
      this.urlMatch = /files\/file\/edit/;

      this.elements({
        'wm_name': 'input[name=wm_name]',
        'wm_type': 'input[name=wm_type]',
        'wm_vrt_alignment': 'input[name=wm_vrt_alignment]',
        'wm_hor_alignment': 'input[name=wm_hor_alignment]',
        'wm_padding': 'input[name=wm_padding]',
        'wm_hor_offset': 'input[name=wm_hor_offset]',
        'wm_vrt_offset': 'input[name=wm_vrt_offset]',

        // Text options
        'wm_use_font': 'a.toggle-btn[data-toggle-for="wm_use_font"]',
        'wm_text': 'input[name=wm_text]',
        'wm_font': 'input[name=wm_font]',
        'wm_font_size': 'input[name=wm_font_size]',
        'wm_font_color': 'input[name=wm_font_color]',
        'wm_use_drop_shadow': 'a.toggle-btn[data-toggle-for="wm_use_drop_shadow"]',
        'wm_shadow_distance': 'input[name=wm_shadow_distance]',
        'wm_shadow_color': 'input[name=wm_shadow_color]',

        // Image options
        'wm_image_path': 'input[name=wm_image_path]',
        'wm_opacity': 'input[name=wm_opacity]',
        'wm_x_transp': 'input[name=wm_x_transp]',
        'wm_y_transp': 'input[name=wm_y_transp]'
      })
    }
    load() {
      cy.contains('Files').click()
      cy.get('div.sidebar h2:nth-of-type(2)').contains('New').click()
    }

    load_edit_for_watermark(number) {
      cy.contains('Watermarks').click()

      cy.get('.wrap tbody tr:nth-child('+number.toString()+') li.edit a').click()
    }
}
export default WatermarkEdit;
