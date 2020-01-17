class MemberGroups < ControlPanelPage
  element :heading, 'div.col.w-12 form h1'
  element :keyword_search, 'input[name=filter_by_keyword]'
  element :new_group, '.section-header__controls a[href$="cp/members/roles/create"]'
  element :perpage_filter, 'div.filters a[data-filter-label^="show"]'

  section :list, MemberGroupsList, 'body'
  section :edit, MemberGroupsEdit, 'body'

  def load
    main_menu.members_btn.click
    click_link 'Roles'
  end
end
