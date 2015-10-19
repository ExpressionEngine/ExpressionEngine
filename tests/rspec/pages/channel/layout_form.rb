class ChannelLayoutForm < ControlPanelPage
	set_url_matcher /channel\/layouts/

	element :heading, 'div.col.w-12 div.box.publish h1'
	element :add_tab_button, 'a.btn.add-tab'

	elements :tabs, 'ul.tabs li a'
	element :publish_tab, 'ul.tabs li:first-child a'
	element :date_tab, 'ul.tabs li:nth-child(2) a'
	element :hide_date_tab, 'ul.tabs li:nth-child(2) span'
	element :tab_3, 'ul.tabs li:nth-child(3) a'
	element :hide_tab_3, 'ul.tabs li:nth-child(3) span'
	element :tab_4, 'ul.tabs li:nth-child(4) a'
	element :hide_tab_4, 'ul.tabs li:nth-child(4) span'

	elements :fields, 'div.tab fieldset.col-group'

	# Layout Options
	element :layout_name, 'form fieldset input[name=layout_name]'
	elements :member_groups, 'form fieldset input[name="member_groups[]"]'
	element :submit_button, 'form fieldset.form-ctrls input[type=submit]'

	element :add_tab_modal, 'div.modal-add-new-tab', visible: false
	element :add_tab_modal_submit_button, 'div.modal-add-new-tab .form-ctrls input.btn', visible: false

	def load
		self.create(1)
	end

    def create(number)
		visit '/system/index.php?/cp/channels/layouts/create/' + number.to_s
    end

    def edit(number)
		visit '/system/index.php?/cp/channels/layouts/edit/' + number.to_s
    end

end