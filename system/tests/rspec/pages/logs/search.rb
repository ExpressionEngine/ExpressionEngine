class SearchLog < ControlPanelPage
	set_url_matcher /logs\/search/

	element :heading, 'div.box form h1'

	element :phrase_search, 'input[name=search]'
	element :submit_button, 'input.submit'

	element :username_filter, 'div.filters ul li:first-child'
	element :username_filter_menu, 'div.filters ul li:first-child div.sub-menu ul', visible: false
	element :username_manual_filter, 'input[name="filter_by_username"]', visible: false

	# element :site_filter, 'div.filters ul li:nth-child(2)'
	# element :site_manual_filter, 'input[name="filter_by_site"]', visible: false

	element :date_filter, 'div.filters ul li:nth-child(2)'
	element :date_filter_menu, 'div.filters ul li:nth-child(2) div.sub-menu ul', visible: false
	element :date_manual_filter, 'input[name="filter_by_date"]', visible: false

	element :perpage_filter, 'div.filters ul li:nth-child(3)'
	element :perpage_filter_menu, 'div.filters ul li:nth-child(3) div.sub-menu ul', visible: false
	element :perpage_manual_filter, 'input[name="perpage"]', visible: false

	element :no_results, 'p.no-results'
	element :remove_all, 'button.btn.remove'

	elements :items, 'section.item-wrap div.item'

	def generate_data(
		count: 250,
		site_id: nil,
		member_id: nil,
		screen_name: nil,
		ip_address: nil,
		timestamp_min: nil,
		timestamp_max: nil,
		type: nil,
		terms: nil
		)
		command = "cd fixtures && php searchLog.php"

		if count
			command += " --count " + count.to_s
		end

		if site_id
			command += " --site-id " + site_id.to_s
		end

		if member_id
			command += " --member-id " + member_id.to_s
		end

		if screen_name
			command += " --screen-name '" + screen_name.to_s + "'"
		end

		if ip_address
			command += " --ip-address '" + ip_address.to_s + "'"
		end

		if timestamp_min
			command += " --timestamp-min " + timestamp_min.to_s
		end

		if timestamp_max
			command += " --timestamp-max " + timestamp_max.to_s
		end

		if type
			command += " --type '" + type.to_s + "'"
		end

		if terms
			command += " --terms '" + terms.to_s + "'"
		end

		command += " > /dev/null 2>&1"

		system(command)
	end

	def load
		self.open_dev_menu
		click_link 'Logs'
		click_link 'Search'
	end
end