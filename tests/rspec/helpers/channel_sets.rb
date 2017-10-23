module ChannelSets
  # Import custom fields for Channel Set (export) testing
  class Importer
    # Create a new importer
    #
    # @param [Object] page The page object so we can @page.load when in debug
    # @param [Boolean] debug: false Set debug to true to create fields from scratch
    # @return [void]
    def initialize(page, debug: false)
      @page = page
      @debug = debug
    end

    # Check debug
    #
    # @return [Boolean] Whether we're debugging or not
    def debug?
      @debug
    end

    # Create a relationship field with specified channels
    #
    # @return [void]
    def relationships_specified_channels
      if debug?
        channel_fields = ChannelFieldForm.new
        channel_fields.create_field(
        group_id: 1,
        type: 'Relationships',
        label: 'Relationships',
        fields: {
          limit: 25,
          relationship_order_field: 'Entry Date',
          relationship_order_dir: 'Descending (Z-A)',
          relationship_allow_multiple: 'n',
          relationship_future: '1'
        }
        ) do |page|
          # TODO: Come back and make sure these _actually_ export properly
          page.find('input[name="relationship_channels[]"][value="2"]').click
          # page.find('input[name="relationship_authors[]"][value="g_1"]').click
          # page.find('input[name="relationship_statuses[]"][value="open"]').click
        end

        @page.load
      else
        $db.query(IO.read('channel_sets/relationships-specified-channels.sql'))
        clear_db_result
      end
    end

    # Create a relationship field with all channels
    #
    # @return [void]
    def relationships_all_channels
      if debug?
        channel_fields = ChannelFieldForm.new
        channel_fields.create_field(
          group_id: 1,
          type: 'Relationships',
          label: 'Relationships',
          fields: {
            limit: 25,
            relationship_order_field: 'Entry Date',
            relationship_order_dir: 'Descending (Z-A)',
            relationship_allow_multiple: 'n',
            relationship_future: '1'
          }
        )

        @page.load
      else
        $db.query(IO.read('channel_sets/relationships-all-channels.sql'))
        clear_db_result
      end
    end

    # Create all other custom fields for export testing
    #
    # @return [void]
    def custom_fields
      if debug?
        channel_fields = ChannelFieldForm.new
        channel_fields.create_field(
          group_id: 1,
          type: 'Checkboxes',
          label: 'Checkboxes',
          fields: {
            field_list_items: "Yes\nNo\nMaybe"
          }
        )
        channel_fields.create_field(
          group_id: 1,
          type: 'Radio Buttons',
          label: 'Radio Buttons',
          fields: {
            field_list_items: "Left\nCenter\nRight"
          }
        )
        channel_fields.create_field(
          group_id: 1,
          type: 'Multi Select',
          label: 'Multi Select',
          fields: {
            field_list_items: "Red\nGreen\nBlue"
          }
        )
        channel_fields.create_field(
          group_id: 1,
          type: 'Select Dropdown',
          label: 'Select Dropdown',
          fields: {
            field_list_items: "Mac\nWindows\nLinux"
          }
        )
        channel_fields.create_field(
          group_id: 1,
          type: 'Select Dropdown',
          label: 'Prepopulated',
          fields: {
            field_pre_populate: 'y'
          }
        )
        channel_fields.create_field(
          group_id: 1,
          type: 'Rich Text Editor',
          label: 'Rich Text Editor',
          fields: {
            field_ta_rows: 20,
            field_text_direction: 'Right to left'
          }
        )
        channel_fields.create_field(
          group_id: 1,
          type: 'Toggle',
          label: 'Toggle'
        )
        channel_fields.create_field(
          group_id: 1,
          type: 'Text Input',
          label: 'Text Input',
          fields: {
            field_maxl: 100,
            field_fmt: 'None',
            field_show_fmt: 'y',
            field_text_direction: 'Right to left',
            field_content_type: 'Decimal',
            field_show_smileys: 'y',
            field_show_file_selector: 'y'
          }
        )
        channel_fields.create_field(
          group_id: 1,
          type: 'Textarea',
          label: 'Textarea',
          fields: {
            field_ta_rows: 20,
            field_fmt: 'None',
            field_show_fmt: 'y',
            field_text_direction: 'Right to left',
            field_show_formatting_btns: 'y',
            field_show_smileys: 'y',
            field_show_file_selector: 'y'
          }
        )
        channel_fields.create_field(
          group_id: 1,
          type: 'URL',
          label: 'URL Field',
          fields: {
            url_scheme_placeholder: '// (Protocol Relative URL)'
          }
        ) do |page|
          page.all('input[name="allowed_url_schemes[]"]').each do |element|
            element.click unless element.checked?
          end
        end

        @page.load
      else
        $db.query(IO.read('channel_sets/custom-fields.sql'))
        clear_db_result
      end
    end

    # Create all other custom fields for export testing
    #
    # @return [void]
    def fluid_field
      $db.query(IO.read('channel_sets/channel-with-fluid-field.sql'))
      clear_db_result
    end

    # Prepare our grid test data for comparison to JSON
    def prepare_test_data(data)
      case data[:type][1]
        when 'file' then
          data[:file_type] = data[:file_type][1]
          data[:allowed_dirs] = data[:allowed_dirs][1]

        when 'relationship' then
          data[:channels] = data[:channels][0]
          data[:categories] = [] #data[:categories][1] #@todo swtich to names?
          data[:authors] = [] #data[:authors][1] #@todo swtich to names?
          data[:statuses] = [] #data[:statuses][1]
          data[:order_field] = data[:order_field][1]
          data[:order_dir] = data[:order_dir][1]

        when 'text' then
          data[:field_fmt] = data[:field_fmt][1]
          data[:field_content_type] = data[:field_content_type][1]
          data[:field_text_direction] = data[:field_text_direction][1]

        when 'textarea' then
          data[:field_fmt] = data[:field_fmt][1]
          data[:field_text_direction] = data[:field_text_direction][1]

        when 'rte' then
          data[:field_text_direction] = data[:field_text_direction][1]

        when 'checkboxes', 'multi_select', 'radio', 'select' then
          data[:field_fmt] = data[:field_fmt][1]
      end

      return data
    end
  end
end
