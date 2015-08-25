class MemberGroupsList < SitePrism::Section
  element :member_actions, 'select[name=bulk_action]', visible: false
  element :member_groups_table, 'table'
end
