class MemberGroupsList < SitePrism::Section
  element :batch_actions, 'select[name=bulk_action]', visible: false
  element :batch_submit, '.tbl-bulk-act button', visible: false
  element :table, 'table'
  elements :groups, 'form table tbody tr'
end
