class MenuSection < SitePrism::Section
  element :members_btn, '.author-menu li:last-child'
  element :dev_menu, '.dev-menu .has-sub'
end
