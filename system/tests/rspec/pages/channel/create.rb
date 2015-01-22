class ChannelCreate < ControlPanelPage

	element :channel_title, 'input[name=channel_title]'
	element :channel_name, 'input[name=channel_name]'
	element :duplicate_channel_prefs, 'select[name=duplicate_channel_prefs]'
	element :status_group, 'select[name=status_group]'
	element :field_group, 'select[name=field_group]'
	elements :cat_group, 'input[name="cat_group[]"]'

	def load
		self.open_dev_menu
		click_link 'Channel Manager'
		click_link 'Create New'
	end

	def load_edit_for_channel(number)
		self.open_dev_menu
		click_link 'Channel Manager'

		find('tr:nth-child('+(number+1).to_s+') li.edit a').click
	end
end