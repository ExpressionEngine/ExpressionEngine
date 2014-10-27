class MenuSection < SitePrism::Section
  element :dev_menu, '.author-menu li:last-child'
  element :members_btn, '.dev-menu .has-sub'
end
