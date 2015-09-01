class Homepage < ControlPanelPage
  element :home, 'a.home'
  element :comment_box, '.home-layout .col-group:nth-child(2) .box'
  element :comment_info, '.home-layout .col-group:nth-child(2) .box .info'

  def load
    home.click
  end
end
