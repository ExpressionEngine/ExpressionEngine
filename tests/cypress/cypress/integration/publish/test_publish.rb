require './bootstrap.rb'
require 'forgery'

feature 'Publish Page - Create', () => {
  before :each do
    cy.auth();
    page = Publish.new
    cy.hasNoErrors()
  }

  it('shows a 404 if there is no channel id', () => {
    page.load()
    page.is_404?.should == true
  }

  it('shows comment fields when comments are enabled by system and channel allows comments', () => {
    eeConfig({item: 'enable_comments', value: 'y')
    page.load()(channel_id:1)
    page.tab_links[1].click()
    page.should have_css('input[name="comment_expiration_date"]')
    page.tab_links[3].click()
    page.should have_css('a[data-toggle-for="allow_comments"]')
  }

  it('does not show comment fields when comments are disabled by system', () => {
    eeConfig({item: 'enable_comments', value: 'n')
    page.load()(channel_id:1)
    page.tab_links[1].click()
    page.should_not have_css('input[name="comment_expiration_date"]')
    page.tab_links[3].click()
    page.should_not have_css('a[data-toggle-for="allow_comments"]')
  }

  it('does not shows comment fields when comments are disabled by system and channel allows comments', () => {
    eeConfig({item: 'enable_comments', value: 'n')
    page.load()(channel_id:2)
    page.tab_links[1].click()
    page.should_not have_css('input[name="comment_expiration_date"]')
    page.tab_links[3].click()
    page.should_not have_css('a[data-toggle-for="allow_comments"]')
  }

  it('selects default categories for new entries', () => {
    page.load()(channel_id: 1)
    page.tab_links[2].click()
    page.all('input[type="checkbox"]').each do |category|
      category.checked?.should == (category.value == '2')
    }
  }

  context 'when using file fields', () => {
    before :each do
      channel_field_form = ChannelFieldForm.new
      channel_field_form.create_field(
        group_id: 1,
        type: 'File',
        label: 'Second File',
        fields: { allowed_directories: 2 }
      )

      page.load()(channel_id: 1)
      page.has_title?
      page.has_url_title?
    }

    it('the file field properly assigns image data when using the filepicker modal in a channel with two file fields', () => {
      page.file_fields.each do |field|
        link = field.find('a', text: 'Choose Existing')
        link.click()

        if link[:class].include? 'js-filter-link'
          field.find('a', text: 'About').click()
        }

        page.wait_until_modal_visible
        page.file_modal.wait_for_filters

        page.file_modal.files[0].click()

        page.wait_until_modal_invisible
      }

      page.chosen_files.should have_at_least(2).items
    }

    it('the file field restricts you to the chosen directory', () => {
      page.file_fields[0].find('a.btn.action', text: 'Choose Existing').click()

      page.wait_until_modal_visible
      page.file_modal.wait_for_filters

      page.file_modal.filters.should have(2).items
      page.file_modal.title.should_not == 'All Files'
      page.file_modal.has_upload_button?

      page.file_modal.filters[-1].click()
      page.file_modal.view_filters[0].click()

      page.file_modal.wait_for_filters
      page.file_modal.filters.should have(2).items
      page.file_modal.title.should_not == 'All Files'
      page.file_modal.has_upload_button?
    }

    it('the file field retains data after being created and edited', () => {
      page.file_fields.each do |field|
        link = field.find('a', text: 'Choose Existing')
        link.click()

        if link[:class].include? 'js-filter-link'
          field.find('a', text: 'About').click()
        }

        page.wait_until_modal_visible
        page.file_modal.wait_for_filters

        page.file_modal.files[0].click()

        page.wait_until_modal_invisible(1)
      }

      page.title.set 'File Field Test'
      page.chosen_files.should have(2).items
      page.submit_buttons[1].click()

      edit = EntryManager.new
      edit.load
      edit.entry_rows[0].find('.toolbar-wrap a[href*="publish/edit/entry"]').click()

      page.chosen_files.should have(2).items
      page.submit

      edit = EntryManager.new
      edit.load
      edit.entry_rows[0].find('.toolbar-wrap a[href*="publish/edit/entry"]').click()

      page.chosen_files.should have(2).items
    }
  }

  context 'when using fluid fields', () => {
    before :each do
      @importer = ChannelSets::Importer.new(page, debug: false)
      @importer.fluid_field
      page.load()(channel_id: 3)

      page.title.set "Fluid Field Test the First"
      page.url_title.set "fluid-field-test-first"

      @available_fields = [
        "A Date",
        "Checkboxes",
        "Electronic-Mail Address",
        "Home Page",
        "Image",
        "Item",
        "Middle Class Text",
        "Multi Select",
        "Radio",
        "Selection",
        "Stupid Grid",
        "Text",
        "Truth or Dare?",
        "YouTube URL"
      ]

      page.fluid_field.actions_menu.name.click()
      page.fluid_field.actions_menu.fields.map {|field| field.text}.should == @available_fields
      page.fluid_field.actions_menu.name.click()
    }

    def add_content(item, skew = 0)
      field_type = item.root_element['data-field-type']
      field = item.field

      case field_type
        when 'date'
          field.find('input[type=text][rel=date-picker]').set (9 + skew).toString() + '/14/2017 2:56 PM'
          page.title.click() // Dismiss the date picker
        when 'checkboxes'
          field.all('input[type=checkbox]')[0 + skew].set true
        when 'email_address'
          field.find('input').set 'rspec-' + skew.toString() + '@example.com'
        when 'url'
          field.find('input').set 'http://www.example.com/page/' + skew.toString()
        when 'file'
          field.find('a', text: 'Choose Existing').click()
          field.find('a', text: 'About').click()
          page.wait_until_modal_visible
          page.file_modal.wait_for_files

          page.file_modal.files[0 + skew].click()

          page.wait_until_modal_invisible
        when 'relationship'
          field.all('input[type=radio]')[0 + skew].set true
        when 'rte'
          field.find('.WysiHat-editor').send_keys Forgery(:lorem_ipsum).paragraphs(
            rand(1..(3 + skew)),
            :html => false,
            :sentences => rand(3..5),
            :separator => "\n\n"
          )
        when 'multi_select'
          field.all('input[type=checkbox]')[0 + skew].set true
        when 'radio'
          field.all('input[type=radio]')[1 + skew].set true
        when 'select'
          field.find('div[data-dropdown-react]').click()
          if skew == 0 then choice = 'Corndog' }
          if skew == 1 then choice = 'Burrito' }
          cy.wait(100)
          find('div[data-dropdown-react] .field-drop-choices label', text: choice).click()
        when 'grid'
          field.find('a[rel="add_row"]').click()
          field.all('input')[0].set 'Lorem' + skew.toString()
          field.all('input')[1].set 'ipsum' + skew.toString()
        when 'textarea'
          field.find('textarea').set Forgery(:lorem_ipsum).paragraphs(
            rand(1..(3 + skew)),
            :html => false,
            :sentences => rand(3..5),
            :separator => "\n\n"
          )
        when 'toggle'
          field.find('.toggle-btn').click()
        when 'text'
          field.find('input').set 'Lorem ipsum dolor sit amet' + skew.toString()
      }
    }

    def check_content(item, skew = 0)
      field_type = item.root_element['data-field-type']
      field = item.field

      case field_type
        when 'date'
          field.find('input[type=text][rel=date-picker]').value.should eq (9 + skew).toString() + '/14/2017 2:56 PM'
        when 'checkboxes'
          field.all('input[type=checkbox]')[0 + skew].checked?.should == true
        when 'email_address'
          field.find('input').value.should eq 'rspec-' + skew.toString() + '@example.com'
        when 'url'
          field.find('input').value.should eq 'http://www.example.com/page/' + skew.toString()
        when 'file'
          field.should have_content('staff_jane')
        when 'relationship'
          field.all('input[type=radio]')[0 + skew].checked?.should == true
        when 'rte'
          field.find('textarea', {:visible => false}).value.should have_content('Lorem ipsum')
        when 'multi_select'
          field.all('input[type=checkbox]')[0 + skew].checked?.should == true
        when 'radio'
          field.all('input[type=radio]')[1 + skew].checked?.should == true
        when 'select'
          if skew == 0 then choice = 'Corndog' }
          if skew == 1 then choice = 'Burrito' }

          field.find('div[data-dropdown-react]').should have_content(choice)
        when 'grid'
          field.all('input')[0].value.should eq 'Lorem' + skew.toString()
          field.all('input')[1].value.should eq 'ipsum' + skew.toString()
        when 'textarea'
          field.find('textarea').value.should have_content('Lorem ipsum')
        when 'toggle'
          field.find('.toggle-btn').click()
        when 'text'
          field.find('input').value.should eq 'Lorem ipsum dolor sit amet' + skew.toString()
      }
    }

    it('adds a field', () => {
      @available_fields.each_with_index do |field, index|
        page.fluid_field.actions_menu.name.click()
        page.fluid_field.actions_menu.fields[index].click()

        page.fluid_field.items[index].title.should have_content(field)
      }

      page.save.click()
      page.get('alert').has_content?('Entry Created').should == true

      // Make sure the fields stuck around after save
      @available_fields.each_with_index do |field, index|
        page.fluid_field.items[index].title.should have_content(field)
        add_content(page.fluid_field.items[index])
      }

      page.save.click()
      page.get('alert').has_content?('Entry Updated').should == true

      @available_fields.each_with_index do |field, index|
        check_content(page.fluid_field.items[index])
      }
    }

    it('adds repeat fields', () => {
      number_of_fields = @available_fields.length

      @available_fields.each_with_index do |field, index|
        page.fluid_field.actions_menu.name.click()
        page.fluid_field.actions_menu.fields[index].click()
        add_content(page.fluid_field.items[index])

        page.fluid_field.items[index].title.should have_content(field)
      }

      @available_fields.each_with_index do |field, index|
        page.fluid_field.actions_menu.name.click()
        page.fluid_field.actions_menu.fields[index].click()
        add_content(page.fluid_field.items[index + number_of_fields], 1)

        page.fluid_field.items[index + number_of_fields].title.should have_content(field)
      }

      page.save.click()
      page.get('alert').has_content?('Entry Created').should == true

      // Make sure the fields stuck around after save
      @available_fields.each_with_index do |field, index|
        page.fluid_field.items[index].title.should have_content(field)
        check_content(page.fluid_field.items[index])

        page.fluid_field.items[index + number_of_fields].title.should have_content(field)
        check_content(page.fluid_field.items[index + number_of_fields], 1)
      }
    }

    // This cannot be tested headlessly yet. See test_statuses.rb:37
    // it('reorders fields', () => {
    // }

    it('removes fields', () => {
      // First: without saving
      @available_fields.each_with_index do |field, index|
        page.fluid_field.actions_menu.name.click()
        page.fluid_field.actions_menu.fields[index].click()
        add_content(page.fluid_field.items[index])

        page.fluid_field.items[index].title.should have_content(field)
      }

      page.fluid_field.items.length.should == @available_fields.length

      page.fluid_field.items.each do |field|
          field.remove.click()
      }

      page.fluid_field.items.length.should == 0

      // Second: after saving
      @available_fields.each_with_index do |field, index|
        page.fluid_field.actions_menu.name.click()
        page.fluid_field.actions_menu.fields[index].click()
        add_content(page.fluid_field.items[index])

        page.fluid_field.items[index].title.should have_content(field)
      }

      page.save.click()
      page.get('alert').has_content?('Entry Created').should == true

      page.fluid_field.items.length.should == @available_fields.length

      page.fluid_field.items.each do |field|
        field.remove.click()
      }

      page.save.click()
      page.get('alert').has_content?('Entry Updated').should == true

      page.fluid_field.items.length.should == 0
    }

    it('keeps data when the entry is invalid', () => {
      @available_fields.each_with_index do |field, index|
        page.fluid_field.actions_menu.name.click()
        page.fluid_field.actions_menu.fields[index].click()
        add_content(page.fluid_field.items[index])

        page.fluid_field.items[index].title.should have_content(field)
      }

      page.title.set ""

      page.save.click()

      @available_fields.each_with_index do |field, index|
        check_content(page.fluid_field.items[index])
      }
    }


  }

}
