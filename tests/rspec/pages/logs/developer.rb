class DeveloperLog < ControlPanelPage
	set_url_matcher /logs\/developer/

	element :heading, 'div.box form h1'

	element :phrase_search, 'input[name=search]'
	element :submit_button, 'input.submit'

	element :date_filter, 'div.filters ul li:first-child'
	element :date_filter_menu, 'div.filters ul li:first-child div.sub-menu ul', visible: false
	element :date_manual_filter, 'input[name="filter_by_username"]', visible: false

	element :perpage_filter, 'div.filters ul li:nth-child(2)'
	element :perpage_filter_menu, 'div.filters ul li:nth-child(2) div.sub-menu ul', visible: false
	element :perpage_manual_filter, 'input[name="perpage"]', visible: false

	element :no_results, 'p.no-results'
	element :remove_all, 'button.btn.remove'

	elements :items, 'section.item-wrap div.item'

	def generate_data(
		count: 250,
		timestamp_min: nil,
		timestamp_max: nil,
		description: nil
		)
		command = "cd fixtures && php developerLog.php"

		if count
			command += " --count " + count.to_s
		end

		if timestamp_min
			command += " --timestamp-min " + timestamp_min.to_s
		end

		if timestamp_max
			command += " --timestamp-max " + timestamp_max.to_s
		end

		if description
			command += " --description '" + description.to_s + "'"
		end

		command += " > /dev/null 2>&1"

		system(command)
	end

	def load
		self.open_dev_menu
		click_link 'Logs'
		click_link 'Developer'
	end
end