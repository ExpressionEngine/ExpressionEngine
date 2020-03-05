class FileModal < SitePrism::Section
  element :title, 'h1'
  element :upload_button, '.tbl-search a.btn'
  elements :filters, '.filters > ul > li > a.has-sub'
  elements :view_filters, '.filters > ul > li:last-child ul a'
  elements :files, 'table tbody td'
end
