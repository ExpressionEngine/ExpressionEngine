class QueryResults < ControlPanelPage

  element :search_field, 'input[name=search]'
  element :search_btn, 'input[type=submit]'
  element :table, 'table'
  elements :rows, 'div.box form table tbody tr'
  elements :first_column, 'table tr td:first-child'

end
