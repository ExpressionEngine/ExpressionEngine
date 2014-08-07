class QueryResults < ControlPanelPage

	element :search_field, 'input[name=search]'
	element :search_btn, 'input[type=submit]'
	element :table, 'table'
	elements :pages, 'div.paginate ul li a'
	elements :rows, 'div.box form table tr'

end