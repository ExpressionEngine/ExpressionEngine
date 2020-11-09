import ControlPanel from '../ControlPanel'

class Publish extends ControlPanel {
  constructor() {
      super()
      this.url = '/admin.php?/cp/publish/create/{channel_id}';

      this.elements({
        'title': 'input[name=title]',
        'url_title': 'input[name=url_title]',
        'save': '.form-btns-top button[name=submit][value=save]',
        'save_and_close': '.form-btns-top button[name=submit][value=save_and_close]',

        'file_fields': 'div[data-file-field-react]',
        'chosen_files': '.fields-upload-chosen:visible img',

        'tab_links': 'ul.tabs li',
        'tabs': '.tab-wrap div.tabs'

        // section :file_modal, FileModal, '.modal-file'
        // section :forum_tab, ForumTab, 'body'
        // section :fluid_field, FluidField, '.fluid-wrap'
      })
    }

}
export default Publish;