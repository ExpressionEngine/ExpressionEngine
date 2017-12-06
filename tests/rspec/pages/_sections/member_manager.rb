class MemberManagerPage < ControlPanelPage

  # Title/header box elements
  element :heading, 'div.col.w-12 div.box form h1'
  element :keyword_search, 'input[name=filter_by_keyword]'

  element :member_actions, 'select[name=bulk_action]'
  element :member_table, 'table'

  elements :members, 'table tbody tr'
  element :selected_member, 'table tbody tr.selected'

  element :id_header, 'div.box form div.tbl-wrap table tr th:first-child'
  element :username_header, 'div.box form div.tbl-wrap table tr th:nth-child(2)'
  element :dates_header, 'div.box form div.tbl-wrap table tr th:nth-child(3)'
  element :member_group_header, 'div.box form div.tbl-wrap table tr th:nth-child(4)'
  element :manage_header, 'div.box form div.tbl-wrap table tr th:nth-child(5)'
  element :checkbox_header, 'div.box form div.tbl-wrap table tr th:nth-child(6)'

  elements :ids, 'div.box form div.tbl-wrap table tr td:first-child'
  elements :usernames, 'div.box form div.tbl-wrap table tr td:nth-child(2) > a'
  elements :emails, 'div.box form div.tbl-wrap table tr td:nth-child(2) span.meta-info a'
  elements :dates, 'div.box form div.tbl-wrap table tr td:nth-child(3)'
  elements :member_groups, 'div.box form div.tbl-wrap table tr td:nth-child(4)'
  elements :manage_actions, 'div.box form div.tbl-wrap table tr td:nth-child(5)'

  element :bulk_action, 'form fieldset.tbl-bulk-act select[name="bulk_action"]'
  element :action_submit_button, 'form fieldset.tbl-bulk-act button.submit'

  element :no_results, 'tr.no-results'
end
