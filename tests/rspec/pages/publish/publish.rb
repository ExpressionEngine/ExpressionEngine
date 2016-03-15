class Publish < ControlPanelPage
  element :title, 'input[name=title]'
  element :url_title, 'input[name=url_title]'

  elements :file_fields, 'a.file-field-filepicker'
  elements :chosen_files, '.file-chosen img'

  elements :tab_links, 'ul.tabs li'
  elements :tabs, '.tab-wrap div.tabs'

  section :file_modal, FileModal, '.modal-file', visible: false
  section :forum_tab, ForumTab, 'body', visible: false

  def load
    visit '/system/index.php?/cp/publish/create/1'
  end
end
