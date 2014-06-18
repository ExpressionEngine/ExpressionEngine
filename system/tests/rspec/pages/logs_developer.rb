class DeveloperLog < ControlPanelPage
  set_url_matcher /logs\/developer/

  element :title, 'div.box form h1'

	element :phrase_search, 'input[name=filter_by_phrase]'
	element :submit_button, 'input.submit'
	element :date_filter, 'select[name=filter_by_date]'
	element :perpage_filter, 'select[name=perpage]'

  element :alert, 'div.alert'
  element :no_results, 'p.no-results'
  element :remove_all, 'a.btn.remove'
  element :pagination, 'div.paginate'

  elements :pages, 'div.paginate ul li a'
  elements :items, 'section.item-wrap div.item'

  def generate_data (count=250)
    system "cd fixtures && php developerLog.php " + count.to_s + " > /dev/null 2>&1"
  end

	def load
    self.generate_data

		self.open_dev_menu
		click_link 'Logs'
		click_link 'Developer'
	end
end