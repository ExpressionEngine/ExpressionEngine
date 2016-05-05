class MemberGroupsList < SitePrism::Section
  element :batch_actions, 'select[name=bulk_action]'
  element :batch_submit, '.tbl-bulk-act button'
  element :table, 'table'
  elements :groups, 'form table tbody tr'
end
