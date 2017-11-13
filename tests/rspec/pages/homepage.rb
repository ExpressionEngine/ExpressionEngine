class Homepage < ControlPanelPage
  element :home, 'a.nav-home'
  element :comment_box, '.home-layout .col-group:nth-child(2) .col:first-child .box'
  element :comment_info, '.home-layout .col-group:nth-child(2) .col:first-child .box .info'

  def load
    home.click
  end
end
