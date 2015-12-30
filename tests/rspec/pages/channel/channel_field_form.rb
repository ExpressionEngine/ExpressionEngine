class ChannelFieldForm < ControlPanelPage
  element :field_type, 'select[name=field_type]'
  element :field_label, 'input[name=field_label]'
  element :field_name, 'input[name=field_name]'

  def load
    visit '/system/index.php?/cp/channels/fields/create/1'
  end

  def load_edit_for_custom_field(name)
    visit '/system/index.php?/cp/channels/fields/1'

    all('table tbody tr').each do |row|
      cell = row.find('td:nth-child(2)')
      if cell.text == name
        row.find('li.edit a').click
        break
      end
    end
    # find('tbody tr:nth-child('+number.to_s+') li.edit a').click
  end

  # Create's a field given certain configuration options
  #
  # @param [Hash] a hash containing various configuration options
  #   group_id: group ID of the field group you want to add the field to, defaults to 1
  #   type: the field type, use a string that matches the item in the dropdown
  #   label: the field's label
  #   name: the field's name
  #   fields: any other fields on the page passed as a hash. key should be the name of the field, value should be the desired value
  def create_field(options = {})
    defaults = {
      group_id: 1,
      fields: {}
    }
    options = defaults.merge(options)

    visit "/system/index.php?/cp/channels/fields/create/#{options[:group_id]}"

    field_type.select options[:type]
    field_label.set options[:label]
    field_name.set options[:name] if options.key? :name

    options[:fields].each do |field, value|
      if page.has_css?("input[name=#{field}]")
        find("input[name=#{field}]").set value
      elsif page.has_css?("select[name=#{field}]")
        find("select[name=#{field}]").select value
      end
    end

    click_button 'Save Field'

    # Double check we're where we should be
    alert.has_content?(options[:label]).should == true
  end
end
