class FluidActionMenu < SitePrism::Section
  element :name, 'a.has-sub'
  element :filter, '.filter-search'
  elements :fields, '.sub-menu li'
end

class FluidField < SitePrism::Section
  section :actions_menu, FluidActionMenu, '.fluid-actions .filters'

  sections :items, '.fluid-item' do
    section :actions_menu, FluidActionMenu, '.filters'

    element :reorder, '.reorder'
    element :title, 'h3'
    element :remove, '.fluid-remove'
    element :instructions, '.fluid-field .field-instruct'
    element :field, '.fluid-field .field-control'
  end
end