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

	def load
    $db.query(IO.read('support/logs/exp_cp_log.sql'))
    clear_db_result

		self.open_dev_menu
		click_link 'Logs'
		click_link 'Control Panel'
	end
end