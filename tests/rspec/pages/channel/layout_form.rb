class ChannelLayoutForm < ControlPanelPage
  set_url_matcher /channel\/layouts/

  element :heading, 'div.col.w-16 div.form-standard h1'
  element :add_tab_button, 'a.add-tab'

  elements :tabs, 'ul.tabs li a'
  element :publish_tab, 'ul.tabs a[rel="t-0"]'
  element :date_tab, 'ul.tabs a[rel="t-1"]'
  element :hide_date_tab, 'ul.tabs a[rel="t-1"] + span'
  element :categories_tab, 'ul.tabs a[rel="t-2"]'
  element :hide_categories_tab, 'ul.tabs a[rel="t-2"] + span'
  element :options_tab, 'ul.tabs a[rel="t-3"]'
  element :hide_options_tab, 'ul.tabs a[rel="t-3"] + span'

  sections :fields, 'div.tab .layout-item' do
    element :reorder, '.reorder'
    element :name, '.field-instruct > label'
    element :field_type, '.field-instruct > label span'
    element :hide, '.field-option-hide input'
    element :collapse, '.field-option-collapse input'
    element :required, '.field-option-required'
  end

  # Layout Options
  element :layout_name, 'form input[name=layout_name]'
  elements :member_groups, 'form input[name="member_groups[]"]'
  element :submit_button, 'div.form-btns.form-btns-top button[value="save_and_close"]'

  element :add_tab_modal, 'div.modal-add-new-tab'
  element :add_tab_modal_tab_name, 'div.modal-add-new-tab input[name="tab_name"]'
  element :add_tab_modal_submit_button, 'div.modal-add-new-tab .form-ctrls .btn'

  def move_tool(node)
    return node.find('.reorder')
  end

  def visibiltiy_tool(node)
    return node.all('.field-option-hide input')
  end

  def minimize_tool(node)
    return node.find('.field-option-collapse input')
  end

  def field_is_required?(node)
    return node.has_selector?('.field-option-required')
  end

  def load
    self.create(1)
  end

  def create(number)
  visit '/admin.php?/cp/channels/layouts/create/' + number.to_s
  end

  def edit(number)
  visit '/admin.php?/cp/channels/layouts/edit/' + number.to_s
  end

end
