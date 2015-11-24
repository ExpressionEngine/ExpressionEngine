require './bootstrap.rb'

feature 'Publish Page' do
  before :each do
    cp_session
    @page = Publish.new
    no_php_js_errors
  end

  context 'when using file fields' do
    before :each do
      channel_field_form = ChannelFieldForm.new
      channel_field_form.create_field(
        group_id: 1,
        type: 'File',
        label: 'Second File',
        fields: { allowed_directories: 'About (Agile Records)' }
      )

      @page.load
      @page.has_title?
      @page.has_url_title?
    end

    it 'the file field properly assigns image data when using the filepicker modal in a channel with two file fields' do
      @page.file_fields.each do |link|
        link.click
        @page.wait_until_modal_visible
        @page.file_modal.wait_for_filters

        @page.file_modal.files[0].click

        @page.wait_until_modal_invisible
      end

      @page.chosen_files.should have_at_least(2).items
    end

    it 'the file field restricts you to the chosen directory' do
      @page.file_fields[1].click

      @page.wait_until_modal_visible
      @page.file_modal.wait_for_filters

      @page.file_modal.filters.should have(2).items
      @page.file_modal.title.should_not == 'All Files'
      @page.file_modal.has_upload_button?

      @page.file_modal.filters[-1].click
      @page.file_modal.view_filters[0].click

      @page.file_modal.wait_for_filters
      @page.file_modal.filters.should have(2).items
      @page.file_modal.title.should_not == 'All Files'
      @page.file_modal.has_upload_button?
    end
  end
end
