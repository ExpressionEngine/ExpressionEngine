require './bootstrap.rb'

feature 'Publish Page' do
  before(:each) do
    cp_session
    @page = Publish.new
    @page.load
    no_php_js_errors
  end

  it 'properly assigns image data when using the filepicker modal in a channel with two file fields' do
    channel_field_form = ChannelFieldForm.new
    channel_field_form.create_field(
      group_id: 1,
      type: 'File',
      label: 'Second File'
    )

    @page.load
    @page.has_title?
    @page.has_url_title?

    all('a.btn.file-field-filepicker').each do |link|
      link.click
      @page.wait_until_modal_visible
      find('.modal-wrap table.file-list tbody tr:first-child').click
      @page.wait_until_modal_invisible
    end

    @page.chosen_files.should have_at_least(2).items
  end
end
