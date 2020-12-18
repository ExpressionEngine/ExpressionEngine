import ControlPanel from '../ControlPanel'

class Publish extends ControlPanel {
  constructor() {
      super()
      this.url = '/admin.php?/cp/publish/create/{channel_id}';

      this.elements({
        'title': 'input[type!=hidden][name=title]',
        'url_title': 'input[type!=hidden][name=url_title]',
        'save': '.tab-bar__right-buttons .form-btns button[name=submit][value=save]',
        'save_and_close': '.tab-bar__right-buttons .form-btns button[name=submit][value=save_and_close]',

        'file_fields': 'div[data-file-field-react]',
        'chosen_files': '.fields-upload-chosen:visible img',

        'tab_links': '.tab-wrap div.tab-bar__tabs .tab-bar__tab',
        'tabs': '.tab-wrap div.tab-bar__tabs'

        // section :file_modal, FileModal, '.modal-file'
        // section :forum_tab, ForumTab, 'body'
        // section :fluid_field, FluidField, '.fluid-wrap'
      })
    }

}
export default Publish;