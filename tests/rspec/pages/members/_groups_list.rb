class MemberGroupsList < SitePrism::Section
  element :actions, 'select[name=bulk_action]', visible: false
  element :table, 'table'
  elements :groups, 'form table tbody tr'
end
