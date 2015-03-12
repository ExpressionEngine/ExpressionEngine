class ChannelManager < ControlPanelPage

	element :table, 'table'
	element :sort_col, 'table th.highlight'
	elements :channels, 'table tr'
	elements :channel_titles, 'table tr td:nth-child(1)'
	elements :channel_names, 'table tr td:nth-child(2)'

	def load
		self.open_dev_menu
		click_link 'Channel Manager'
	end
end