class CpLog < ControlPanelPage
  set_url_matcher /logs\/cp/

  element :title, 'div.box form h1'

	element :phrase_search, 'input[name=filter_by_phrase]'
	element :submit_button, 'input.submit'
	element :username_filter, 'select[name=filter_by_username]'
	element :site_filter, 'select[name=filter_by_site]'
	element :date_filter, 'select[name=filter_by_date]'
	element :perpage_filter, 'select[name=perpage]'

  element :alert, 'div.alert'
  element :no_results, 'p.no-results'
  element :remove_all, 'a.btn.remove'
  element :pagination, 'div.paginate'

  elements :pages, 'div.paginate ul li a'
  elements :items, 'section.item-wrap div.item'

  def generate_data (count: 250, site_id: nil, member_id: nil, username: nil, ip_address: nil, timestamp_min: nil, timestamp_max: nil)
    command = "cd fixtures && php cpLog.php"

    if count
      command += " --count " + count.to_s
    end

    if site_id
      command += " --site-id " + site_id.to_s
    end

    if member_id
      command += " --member-id " + member_id.to_s
    end

    if username
      command += " --username '" + username.to_s + "'"
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

    command += " > /dev/null 2>&1"

    system(command)
  end

	def load
		self.open_dev_menu
		click_link 'Logs'
		click_link 'Control Panel'
	end
end