#
# This is basically a factory for GridSettingsColumn objects,
# which allows a column on the Grid settings page to be repesented
# in Ruby for easy filling and validating of data
#
module GridSettings

	extend Capybara::DSL
	extend RSpec::Expectations
	extend RSpec::Matchers

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
			:text_input_col => {
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
			:rich_textarea_col => {
				:type => ['Textarea (Rich Text)', 'rte'],
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
				:field_list_items => "Option 1\nOption & 2",
			},
			:multiselect_col => {
				:type => ['Multi Select', 'multi_select'],
				:label => 'Multi Select',
				:name => 'multi_select',
				:instructions => '',
				:required => false,
				:searchable => false,
				:width => '30',
				:field_fmt => ['XHTML', 'xhtml'],
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
				:field_list_items => "Option 1\nOption & 2",
			}
		}
	end

	# Get nth column
	def self.column(number)
		node = find('.grid-wrap .grid-item:nth-child('+number.to_s+')')
		GridSettingsColumn.new(node)
	end

	# Clicks the button to add a new column to the settings view, and
	# returns a new GridSettingsColumn object representing the column
	def self.add_column
		find('.grid-wrap .grid-item:last-child li.add a').click
		sleep 0.1 # Wait for DOM
		node = find('.grid-wrap .grid-item:last-child')
		GridSettingsColumn.new(node)
	end

	# Clicks the Copy button on a Grid settings column and returns the
	# newly cloned column as a GridSettingsColumn object
	def self.clone_column(number)
		self::column(number).node.find('li.copy a').click
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
		self.load_elements
	end

	# Finds elements and assigns them to instance variables so we're
	# not constantly finding them using selectors
	def load_elements
		@type = @node.find('select.grid_col_select')
		@label = @node.find('[name*="col_label"]')
		@name = @node.find('[name*="col_name"]')
		@instructions = @node.find('[name*="col_instructions"]')
		@required = @node.find('[name*="col_required"]')
		@searchable = @node.find('[name*="col_search"]')
		@width = @node.find('[name*="col_width"]')

		set_type_obj(@type.value)
	end

	# Given a hash of data, fills the various form fields
	def fill_data(data)
		@type.select data[:type][0]
		@label.set data[:label]
		@instructions.set data[:instructions]
		@required.set data[:required]
		@searchable.set data[:searchable]
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
		@required.checked?.should == data[:required]
		@searchable.checked?.should == data[:searchable]

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
		@localized_y = @node.find('[name*="localize"][value=y]')
		@localized_n = @node.find('[name*="localize"][value=n]')
	end

	def fill_data(data)
		if data[:localized]
			@localized_y.click
		else
			@localized_n.click
		end
	end

	def validate(data)
		@localized_y.checked?.should == data[:localized]
		@localized_n.checked?.should == !data[:localized]
	end
end

class GridSettingsColumnTypeFile

	def initialize(node)
		@node = node
		self.load_elements
	end

	def load_elements
		@file_type = @node.find('[name*="field_content_type"]')
		@allowed_dirs = @node.find('[name*="allowed_directories"]')
		@show_existing_y = @node.find('[name*="show_existing"][value=y]')
		@show_existing_n = @node.find('[name*="show_existing"][value=n]')
		@num_existing = @node.find('[name*="num_existing"]')
	end

	def fill_data(data)
		@file_type.select data[:file_type][0]
		@allowed_dirs.select data[:allowed_dirs][0]
		if data[:show_existing]
			@show_existing_y.click
		else
			@show_existing_n.click
		end
		@num_existing.set data[:num_existing]
	end

	def validate(data)
		@file_type.value.should == data[:file_type][1]
		@allowed_dirs.value.should == data[:allowed_dirs][1]
		@num_existing.value.should == data[:num_existing]
		@show_existing_y.checked?.should == data[:show_existing]
		@show_existing_n.checked?.should == !data[:show_existing]
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
		@field_fmt = @node.find('[name*="field_fmt"]')
		@field_content_type = @node.find('[name*="field_content_type"]')
		@field_text_direction = @node.find('[name*="field_text_direction"]')
		@field_maxl = @node.find('[name*="field_maxl"]')
	end

	def fill_data(data)
		@field_fmt.select data[:field_fmt][0]
		@field_content_type.select data[:field_content_type][0]
		@field_text_direction.select data[:field_text_direction][0]
		@field_maxl.set data[:field_maxl]
	end

	def validate(data)
		@field_fmt.value.should == data[:field_fmt][1]
		@field_content_type.value.should == data[:field_content_type][1]
		@field_text_direction.value.should == data[:field_text_direction][1]
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
	end

	def fill_data(data)
		@field_fmt.select data[:field_fmt][0]
		@field_list_items.set data[:field_list_items]
	end

	def validate(data)
		@field_fmt.value.should == data[:field_fmt][1]
		@field_list_items.value.should == data[:field_list_items]
	end
end
