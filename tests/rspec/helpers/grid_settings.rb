#
# This is basically a factory for GridSettingsColumn objects,
# which allows a column on the Grid settings page to be repesented
# in Ruby for easy filling and validating of data
#
module GridSettings

  extend Capybara::DSL
  extend RSpec::Expectations
  extend RSpec::Matchers

  def self.populate_grid_settings
    self.test_data.each_with_index do |column_data, index|
      # First column is already there, so call add_column for
      # subsequent columns after index 0
      column = index == 0 ? self.column(1) : self.add_column
      column.fill_data(column_data[1])
    end

    no_php_js_errors
  end

  def self.test_data
    return {
      :date_col => {
        :type => ['Date', 'date'],
        :label => 'Date',
        :name => 'date',
        :instructions => 'Some instructions',
        :required => true,
        :searchable => true,
        :width => '100',
        :localized => false
      },
      :file_col => {
        :type => ['File', 'file'],
        :label => 'File',
        :name => 'file',
        :instructions => '',
        :required => true,
        :searchable => false,
        :width => '90',
        :file_type => ['Image', 'image'],
        :allowed_dirs => ['Main Upload Directory', '1'],
        :show_existing => false,
        :num_existing => '100'
      },
      :relationship_col => {
        :type => ['Relationships','relationship'],
        :label => 'Relationships',
        :name => 'relationships',
        :instructions => '',
        :required => false,
        :searchable => true,
        :width => '80',
        :expired => true,
        :future => true,
        :channels => [['Information Pages', 'News'], ['2', '1']],
        :categories => [['Bands', 'Site Info'], ['2', '4']],
        :authors => [['Super Admins'], ['g_1']],
        :statuses => [['Open', 'Featured'], ['open', 'Featured']],
        :limit => '500',
        :order_field => ['Entry Date', 'entry_date'],
        :order_dir => ['Descending', 'desc'],
        :allow_multiple => true
      },
      :text_col => {
        :type => ['Text Input', 'text'],
        :label => 'Text Input',
        :name => 'text_input',
        :instructions => '',
        :required => false,
        :searchable => false,
        :width => '70',
        :field_fmt => ['XHTML', 'xhtml'],
        :field_content_type => ['Number', 'numeric'],
        :field_text_direction => ['Right to left', 'rtl'],
        :field_maxl => '500'
      },
      :textarea_col => {
        :type => ['Textarea', 'textarea'],
        :label => 'Textarea',
        :name => 'textarea',
        :instructions => '',
        :required => false,
        :searchable => false,
        :width => '60',
        :field_fmt => ['XHTML', 'xhtml'],
        :field_text_direction => ['Right to left', 'rtl'],
        :field_ta_rows => '10',
        :show_formatting_buttons => true,
      },
      :rte_col => {
        :type => ['Rich Text Editor', 'rte'],
        :label => 'Rich Textarea',
        :name => 'rich_textarea',
        :instructions => '',
        :required => false,
        :searchable => false,
        :width => '50',
        :field_text_direction => ['Right to left', 'rtl'],
        :field_ta_rows => '10',
      },
      :checkboxes_col => {
        :type => ['Checkboxes', 'checkboxes'],
        :label => 'Checkboxes',
        :name => 'checkboxes',
        :instructions => '',
        :required => false,
        :searchable => false,
        :width => '40',
        :field_fmt => ['XHTML', 'xhtml'],
        :field_pre_populate => 'n',
        :field_list_items => "Option 1\nOption & 2",
      },
      :multi_select_col => {
        :type => ['Multi Select', 'multi_select'],
        :label => 'Multi Select',
        :name => 'multi_select',
        :instructions => '',
        :required => false,
        :searchable => false,
        :width => '30',
        :field_fmt => ['XHTML', 'xhtml'],
        :field_pre_populate => 'n',
        :field_list_items => "Option 1\nOption & 2",
      },
      :radio_col => {
        :type => ['Radio Buttons', 'radio'],
        :label => 'Radio Buttons',
        :name => 'radio_buttons',
        :instructions => '',
        :required => false,
        :searchable => false,
        :width => '20',
        :field_fmt => ['XHTML', 'xhtml'],
        :field_pre_populate => 'n',
        :field_list_items => "Option 1\nOption & 2",
      },
      :select_col => {
        :type => ['Select Dropdown', 'select'],
        :label => 'Select Dropdown',
        :name => 'select_dropdown',
        :instructions => '',
        :required => false,
        :searchable => false,
        :width => '10',
        :field_fmt => ['XHTML', 'xhtml'],
        :field_pre_populate => 'n',
        :field_list_items => "Option 1\nOption & 2",
      },
      :toggle_col => {
        :type => ['Toggle', 'toggle'],
        :label => 'Toggle',
        :name => 'toggle',
        :instructions => '',
        :required => false,
        :searchable => false,
        :width => '10',
        :field_default_value => '1',
      },
      :email_address_col => {
        :type => ['Email Address', 'email_address'],
        :label => 'Email Address',
        :name => 'email_address',
        :instructions => '',
        :required => false,
        :searchable => false,
        :width => '10',
      },
      :url_col => {
        :type => ['URL', 'url'],
        :label => 'URL',
        :name => 'url',
        :instructions => '',
        :required => false,
        :searchable => false,
        :width => '10',
        :allowed_url_schemes => ['http://', 'https://'],
        :url_scheme_placeholder => 'http://',
      },
    }
  end

  # Get nth column
  def self.column(number)
    node = find('.fields-grid-setup .fields-grid-item:nth-child('+number.to_s+')')
    GridSettingsColumn.new(node)
  end

  # Clicks the button to add a new column to the settings view, and
  # returns a new GridSettingsColumn object representing the column
  def self.add_column
    find('.fields-grid-setup .fields-grid-item:last-child > .fields-grid-tools > a.fields-grid-tool-add').click
    sleep 0.2 # Wait for DOM
    node = find('.fields-grid-setup .fields-grid-item:last-child')
    GridSettingsColumn.new(node)
  end

  # Clicks the Copy button on a Grid settings column and returns the
  # newly cloned column as a GridSettingsColumn object
  def self.clone_column(number)
    self::column(number).node.find('.fields-grid-tools:last-child > a.fields-grid-tool-copy').click
    self::column(number + 1)
  end

  # Given a set of checkboxes and array of values, clicks the checkboxes
  # with the corresponding values
  def self.click_checkbox(elements, value)
    elements.each do |checkbox|
      if checkbox.value == value
        checkbox.click
        break
      end
    end
  end

  # Given a set of checkboxes, checks to make sure only the ones with
  # values present in the passed values array are checked
  def self.checkboxes_should_have_checked_values(elements, values)
    elements.each do |checkbox|
      checkbox.checked?.should == values.include?(checkbox.value)
    end
  end
end

class GridSettingsColumn

  attr_reader :node, :type, :label, :name, :instructions, :width, :required, :searchable

  def initialize(node)
    @node = node
    if ! node[:class].include?("fields-grid-item---open")
      node.find('a.fields-grid-tool-expand').click
    end
    self.load_elements
  end

  # Finds elements and assigns them to instance variables so we're
  # not constantly finding them using selectors
  def load_elements
    @col_type = @node.find('div[data-input-value*="col_type"]')
    @type = @node.find('[name*="col_type"]', visible: false)
    @label = @node.find('[name*="col_label"]')
    @name = @node.find('[name*="col_name"]')
    @instructions = @node.find('[name*="col_instructions"]')
    @required = @node.find('[data-toggle-for*="col_required"]')
    @required_input = @node.find('[name*="col_required"]', visible: false)
    @searchable = @node.find('[data-toggle-for*="col_search"]')
    @searchable_input = @node.find('[name*="col_search"]', visible: false)
    @width = @node.find('[name*="col_width"]')

    set_type_obj(@type.value)
  end

  # Given a hash of data, fills the various form fields
  def fill_data(data)
    @col_type.click
    @col_type.find('.field-drop-choices label', text: data[:type][0]).click
    @label.set data[:label]
    @instructions.set data[:instructions]
    if data[:required] == 'y'
      @required.click
    end
    if data[:searchable] == 'y'
      @searchable.click
    end
    @width.set data[:width]

    set_type_obj(data[:type][1])

    @type_obj.fill_data(data)
  end

  # Given a hash of data, compares that data to what's in the form
  def validate(data)
    @type.value.should == data[:type][1]
    @label.value.should == data[:label]
    @name.value.should == data[:name]
    @instructions.value.should == data[:instructions]
    @width.value.should == data[:width]
    @required_input.value.should == data[:required]
    @searchable_input.value.should == data[:searchable]

    @type_obj.validate(data)
  end

  # Given a type of column, sets the appropriate object as an instance
  # variable for filling and validating data in that column
  def set_type_obj(type)
    if type == 'date'
      @type_obj = GridSettingsColumnTypeDate.new(@node)
    elsif type == 'file'
      @type_obj = GridSettingsColumnTypeFile.new(@node)
    elsif type == 'relationship'
      @type_obj = GridSettingsColumnTypeRelationships.new(@node)
    elsif type == 'text'
      @type_obj = GridSettingsColumnTypeTextInput.new(@node)
    elsif type == 'textarea'
      @type_obj = GridSettingsColumnTypeTextarea.new(@node)
    elsif type == 'rte'
      @type_obj = GridSettingsColumnTypeRichTextarea.new(@node)
    elsif type == 'toggle'
      @type_obj = GridSettingsColumnTypeToggle.new(@node)
    elsif type == 'email_address'
      @type_obj = GridSettingsColumnTypeEmailAddress.new(@node)
    elsif type == 'url'
      @type_obj = GridSettingsColumnTypeUrl.new(@node)
    elsif ['checkboxes', 'multi_select', 'radio', 'select'].include? type
      @type_obj = GridSettingsColumnTypeMuliselect.new(@node)
    else
      raise StandardError
    end
  end

  # Clicks the delete link on the current settings column
  def delete
    @node.find('li.remove a').click
    sleep 0.5 # Wait for DOM animation
  end
end

class GridSettingsColumnTypeDate

  def initialize(node)
    @node = node
    self.load_elements
  end

  def load_elements
    @localized = @node.find('[data-toggle-for*="localize"]')
    @localized_input = @node.find('[name*="localize"]', visible: false)
  end

  def fill_data(data)
    if ! data[:localized] # On by default, so only click to turn off
      @localized.click
    end
  end

  def validate(data)
    if data[:localized]
      @localized[:class].include?('on').should == true
    else
      @localized[:class].include?('off').should == true
    end
    @localized_input.value.should == data[:localized]
  end
end

class GridSettingsColumnTypeFile

  def initialize(node)
    @node = node
    self.load_elements
  end

  def load_elements
    @show_existing = @node.find('[data-toggle-for*="show_existing"]')
    @show_existing_input = @node.find('[name*="show_existing"]', visible: false)
    @num_existing = @node.find('[name*="num_existing"]')
  end

  def fill_data(data)
    @file_type = @node.find('[name*="field_content_type"][value="'+data[:file_type][1]+'"]')
    @file_type.click
    @allowed_dirs = @node.find('[name*="allowed_directories"][value="'+data[:allowed_dirs][1]+'"]')
    @allowed_dirs.click
    if ! data[:show_existing] # On by default, so only click to turn off
      @show_existing.click
    end
    @num_existing.set data[:num_existing]
  end

  def validate(data)
    @file_type.checked?.should == true
    @allowed_dirs.checked?.should == true
    @num_existing.value.should == data[:num_existing]
    if data[:show_existing]
      @show_existing[:class].include?('on').should == true
    else
      @show_existing[:class].include?('off').should == true
    end
    @show_existing_input.value.should == data[:show_existing]
  end
end

class GridSettingsColumnTypeRelationships

  def initialize(node)
    @node = node
    self.load_elements
  end

  def load_elements
    @expired = @node.find('[name*="expired"]')
    @future = @node.find('[name*="future"]')
    @channels = @node.all('[name*="channels"]')
    @categories = @node.all('[name*="categories"]')
    @authors = @node.all('[name*="authors"]')
    @statuses = @node.all('[name*="statuses"]')
    @limit = @node.find('[name*="limit"]')
    @order_field = @node.find('[name*="order_field"]')
    @order_dir = @node.find('[name*="order_dir"]')
    @allow_multiple = @node.all('[name*="allow_multiple"]')
  end

  def fill_data(data)
    @expired.set data[:expired]
    @future.set data[:future]

    # Uncheck default "Any X"
    GridSettings::click_checkbox(@channels, '--')
    data[:channels][1].each do |channel|
      GridSettings::click_checkbox(@channels, channel)
    end

    GridSettings::click_checkbox(@categories, '--')
    data[:categories][1].each do |category|
      GridSettings::click_checkbox(@categories, category)
    end

    GridSettings::click_checkbox(@authors, '--')
    data[:authors][1].each do |author|
      GridSettings::click_checkbox(@authors, author)
    end

    GridSettings::click_checkbox(@statuses, '--')
    data[:statuses][1].each do |status|
      GridSettings::click_checkbox(@statuses, status)
    end

    @limit.set data[:limit]
    @order_field.select data[:order_field][0]
    @order_dir.select data[:order_dir][0]

    if data[:allow_multiple]
      @allow_multiple[0].click
    else
      @allow_multiple[1].click
    end
  end

  def validate(data)
    GridSettings::checkboxes_should_have_checked_values(@channels, data[:channels][1])
    GridSettings::checkboxes_should_have_checked_values(@categories, data[:categories][1])
    GridSettings::checkboxes_should_have_checked_values(@authors, data[:authors][1])
    GridSettings::checkboxes_should_have_checked_values(@statuses, data[:statuses][1])
    @limit.value.should == data[:limit]
    @order_field.value.should == data[:order_field][1]
    @order_dir.value.should == data[:order_dir][1]
    @expired.checked?.should == data[:expired]
    @future.checked?.should == data[:future]
    @allow_multiple[0].checked?.should == data[:allow_multiple]
    @allow_multiple[1].checked?.should == !data[:allow_multiple]
  end
end

class GridSettingsColumnTypeTextInput

  def initialize(node)
    @node = node
    self.load_elements
  end

  def load_elements
    @field_maxl = @node.find('[name*="field_maxl"]')
  end

  def fill_data(data)
    @field_fmt = @node.find('[name*="field_fmt"][value="'+data[:field_fmt][0]+'"]')
    @field_fmt.click
    @field_content_type = @node.find('[name*="field_content_type"][value="'+data[:field_content_type][0]+'"]')
    @field_content_type.click
    @field_text_direction = @node.find('[name*="field_text_direction"][value="'+data[:field_text_direction][0]+'"]')
    @field_text_direction.click
    @field_maxl.set data[:field_maxl]
  end

  def validate(data)
    @field_fmt.checked?.should == true
    @field_content_type.checked?.should == true
    @field_text_direction.checked?.should == true
    @field_maxl.value.should == data[:field_maxl]
  end
end

class GridSettingsColumnTypeTextarea

  def initialize(node)
    @node = node
    self.load_elements
  end

  def load_elements
    @field_fmt = @node.find('[name*="field_fmt"]')
    @field_ta_rows = @node.find('[name*="field_ta_rows"]')
    @field_text_direction = @node.find('[name*="field_text_direction"]')
    @show_formatting_buttons = @node.find('[name*="show_formatting_btns"]')
  end

  def fill_data(data)
    @field_fmt.select data[:field_fmt][0]
    @field_ta_rows.set data[:field_ta_rows]
    @field_text_direction.select data[:field_text_direction][0]
    @show_formatting_buttons.set data[:show_formatting_buttons]
  end

  def validate(data)
    @field_fmt.value.should == data[:field_fmt][1]
    @field_ta_rows.value.should == data[:field_ta_rows]
    @field_text_direction.value.should == data[:field_text_direction][1]
    @show_formatting_buttons.checked?.should == data[:show_formatting_buttons]
  end
end

class GridSettingsColumnTypeRichTextarea

  def initialize(node)
    @node = node
    self.load_elements
  end

  def load_elements
    @field_ta_rows = @node.find('[name*="field_ta_rows"]')
    @field_text_direction = @node.find('[name*="field_text_direction"]')
  end

  def fill_data(data)
    @field_ta_rows.set data[:field_ta_rows]
    @field_text_direction.select data[:field_text_direction][0]
  end

  def validate(data)
    @field_ta_rows.value.should == data[:field_ta_rows]
    @field_text_direction.value.should == data[:field_text_direction][1]
  end
end

class GridSettingsColumnTypeMuliselect
  def initialize(node)
    @node = node
    self.load_elements
  end

  def load_elements
    @field_fmt = @node.find('[name*="field_fmt"]')
    @field_list_items = @node.find('[name*="field_list_items"]')
    @field_pre_populate_v = @node.find('[name*="field_pre_populate"][value=v]')
    @field_pre_populate_n = @node.find('[name*="field_pre_populate"][value=n]')
  end

  def fill_data(data)
    @field_fmt.select data[:field_fmt][0]
    @field_list_items.set data[:field_list_items]
    if data[:field_pre_populate] == 'v'
      @field_pre_populate_v.click
    else
      @field_pre_populate_n.click
    end
  end

  def validate(data)
    @field_fmt.value.should == data[:field_fmt][1]
    @field_list_items.value.should == data[:field_list_items]
    @field_pre_populate_v.checked?.should == (data[:field_pre_populate] == 'v')
    @field_pre_populate_n.checked?.should == (data[:field_pre_populate] == 'n')
  end
end

class GridSettingsColumnTypeToggle
  def initialize(node)
    @node = node
    self.load_elements
  end

  def load_elements
    @field_default_value = @node.find('[name*="field_default_value"]', :visible => false)
    @field_default_value_btn = @node.find('.toggle-btn')
  end

  def fill_data(data)
    if data[:field_default_value] == '1'
      @field_default_value_btn.click
    end
  end

  def validate(data)
    @field_default_value.value.should == data[:field_default_value]
    if data[:field_default_value] == '1'
      @node.find('.toggle-btn.on').should_not == nil
    else
      @node.find('.toggle-btn.off').should_not == nil
    end
  end
end

class GridSettingsColumnTypeEmailAddress
  def initialize(node)
    @node = node
  end

  def load_elements
  end

  def fill_data(data)
  end

  def validate(data)
  end
end

class GridSettingsColumnTypeUrl
  def initialize(node)
    @node = node
    self.load_elements
  end

  def load_elements
    @allowed_url_schemes = @node.all('[name*="allowed_url_schemes"]')
    @url_scheme_placeholder = @node.find('[name*="url_scheme_placeholder"]')
  end

  def fill_data(data)
    @allowed_url_schemes.each do |checkbox|
      checkbox.set(false)
    end
    data[:allowed_url_schemes].each do |scheme|
      @node.find("[name*='allowed_url_schemes'][value='#{scheme}']").set(true)
    end
    @url_scheme_placeholder.set data[:url_scheme_placeholder]
  end

  def validate(data)
    data[:allowed_url_schemes].each do |scheme|
      @node.find("[name*='allowed_url_schemes'][value='#{scheme}']").checked?.should == true
    end
    @url_scheme_placeholder.value.should == data[:url_scheme_placeholder]
  end
end
