class Statuses < ControlPanelPage

	elements :statuses, 'table tbody tr'
	elements :status_names, 'table tr td:nth-child(3)'

	def load_view_for_status_group(number)
		self.open_dev_menu
		click_link 'Channel Manager'
		click_link 'Status Groups'

		find('tbody tr:nth-child('+number.to_s+') li.view a').click
	end
end