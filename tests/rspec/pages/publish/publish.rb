class Publish < ControlPanelPage
  set_url '/admin.php?/cp/publish/create/{channel_id}'

  element :title, 'input[name=title]'
  element :url_title, 'input[name=url_title]'
  element :save, '.form-btns-top button[name=submit][value=save]'
  element :save_and_close, '.form-btns-top button[name=submit][value=save_and_close]'

  elements :file_fields, 'div[data-file-field-react]'
  elements :chosen_files, '.fields-upload-chosen img'

  elements :tab_links, 'ul.tabs li'
  elements :tabs, '.tab-wrap div.tabs'

  section :file_modal, FileModal, '.modal-file'
  section :forum_tab, ForumTab, 'body'
  section :fluid_field, FluidField, '.fluid-wrap'
end
