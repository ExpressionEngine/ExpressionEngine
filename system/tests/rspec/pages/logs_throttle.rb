class ThrottleLog < ControlPanelPage
  set_url_matcher /logs\/throttle/

  element :title, 'div.box form h1'

	element :phrase_search, 'input[name=filter_by_phrase]'
	element :submit_button, 'input.submit'
	element :perpage_filter, 'select[name=perpage]'

  element :alert, 'div.alert'
  element :no_results, 'p.no-results'
  element :remove_all, 'a.btn.remove'
  element :pagination, 'div.paginate'

  elements :pages, 'div.paginate ul li a'
  elements :items, 'section.item-wrap div.item'

  def generate_data (count: 250, ip_address: nil, timestamp_min: nil, timestamp_max: nil, hits: nil, locked_out: nil)
    command = "cd fixtures && php throttlingLog.php"

    if count
      command += " --count " + count.to_s
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

    if hits
      command += " --hits " + hits.to_s
    end

    if locked_out
      command += " --locked-out"
    end

    command += " > /dev/null 2>&1"

    system(command)
  end

	def load
		self.open_dev_menu
		click_link 'Logs'
		click_link 'Throttling'
	end
end