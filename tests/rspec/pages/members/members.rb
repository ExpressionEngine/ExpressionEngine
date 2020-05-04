class Members < MemberManagerPage

  def load
    main_menu.members_btn.click
    click_link 'All Members'
  end
end
