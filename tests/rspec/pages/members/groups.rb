class MemberGroups < ControlPanelPage
  element :search, 'input[name=search]'
  element :new_group, '.sidebar h2 a[href$="cp/members/groups/create"]'

  section :list, MemberGroupsList, 'body'
  section :edit, MemberGroupsEdit, 'body'

  def load
    main_menu.members_btn.click
    find('.sidebar h2 a[href$="cp/members/groups"]').click
  end
end
