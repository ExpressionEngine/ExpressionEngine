class Publish < ControlPanelPage
  element :title, 'input[name=title]'
  element :url_title, 'input[name=url_title]'
  elements :chosen_files, '.file-chosen img'

  def load
    visit '/system/index.php?/cp/publish/create/1'
  end
end
