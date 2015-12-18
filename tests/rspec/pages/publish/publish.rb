class Publish < ControlPanelPage
  element :title, 'input[name=title]'
  element :url_title, 'input[name=url_title]'

  elements :file_fields, 'a.file-field-filepicker'
  elements :chosen_files, '.file-chosen img'

  section :file_modal, FileModal, '.modal-file', visible: false

  def load
    visit '/system/index.php?/cp/publish/create/1'
  end
end
