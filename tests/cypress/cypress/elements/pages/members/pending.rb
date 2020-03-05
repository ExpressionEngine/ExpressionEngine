class PendingMembers < MemberManagerPage

  def load
    main_menu.members_btn.click
    click_link 'Pending Activation'
  end
end
