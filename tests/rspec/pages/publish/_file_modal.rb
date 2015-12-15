class FileModal < SitePrism::Section
  element :title, 'h1'
  element :upload_button, '.tbl-search a.btn'
  elements :filters, '.filters > ul > li > a.has-sub', visible: false
  elements :view_filters, '.filters > ul > li:last-child ul a', visible: false
  elements :files, 'table tbody td', visible: false
end
