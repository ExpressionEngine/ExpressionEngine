class ChannelLayoutForm < ControlPanelPage
	set_url_matcher /channel\/layout/

	element :heading, 'div.col.w-16 div.box.publish h1'
	element :add_tab_button, 'a.btn.add-tab'

	elements :fields, 'div.tab fieldset.col-group'

	# Layout Options
	element :layout_name, 'form fieldset input[name=laout_name]'
	elements :member_groups, 'form fieldset input[name="member_groups[]"]'
	element :submit_button, 'form fieldset.form-ctrls input[type=submit]'

	element :add_tab_modal, 'div.modal-add-new-tab', visible: false
	element :add_tab_modal_submit_button, 'div.modal-add-new-tab .form-ctrls input.btn', visible: false

end