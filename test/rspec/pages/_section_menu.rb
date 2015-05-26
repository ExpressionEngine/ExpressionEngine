class MenuSection < SitePrism::Section
  element :dev_menu, '.dev-menu .has-sub'
  element :members_btn, '.author-menu li:last-child'
end
