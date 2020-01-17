class MemberGroupsList < SitePrism::Section
  element :batch_actions, 'select[name=bulk_action]'
  element :batch_submit, '.tbl-bulk-act input.submit'
  element :groups_table, 'ul.tbl-list'
  elements :groups, 'form ul.tbl-list li div.tbl-row'
  element :no_results, '.tbl-list li div.tbl-row.no-results'
end
