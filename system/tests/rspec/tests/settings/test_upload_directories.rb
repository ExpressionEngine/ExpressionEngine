require './bootstrap.rb'

feature 'Upload Directories' do

  before(:each) do
    cp_session
    @page = UploadDirectories.new
    @page.load
    no_php_js_errors
  end

  it 'shows the Upload Directories listing page' do
    @page.all_there?.should == true
  end

  def get_directories
    directories = []
    $db.query('SELECT name FROM exp_upload_prefs').each(:as => :array) do |row|
      directories << row[0]
    end
    clear_db_result

    return directories
  end

  it 'should list the upload directories' do
    directories = get_directories

    @page.directory_names.map {|source| source.text}.should == directories
    @page.should have(directories.count).directory_names
  end

  it 'should sort the list of upload directories' do
    @page.sort_col.text.should eq 'ID#'
    @page.sort_links[0].click

    directories = get_directories

    @page.directory_names.map {|source| source.text}.should == directories.reverse
    @page.should have(directories.count).directory_names
    @page.sort_col.text.should eq 'ID#'

    # Sort alphabetically
    @page.sort_links[1].click
    @page.directory_names.map {|source| source.text}.should == directories.sort
    @page.sort_col.text.should eq 'Directory'

    # Sort reverse alphabetically
    @page.sort_links[1].click
    @page.directory_names.map {|source| source.text}.should == directories.sort { |x,y| y <=> x }
    @page.sort_col.text.should eq 'Directory'
  end

  it 'should delete an upload directory' do
    @page.directories[2].find('input[type="checkbox"]').set true
    @page.bulk_action.select 'Remove'
    @page.action_submit_button.click
    @page.wait_until_modal_visible
    @page.modal.should have_text 'Upload directory: About'
    @page.modal_submit_button.click
    no_php_js_errors

    @page.should have_alert
    @page.alert[:class].should include 'success'
    @page.alert.text.should include 'Upload directories removed'
    @page.alert.text.should include '1 upload directories were removed.'
    @page.directory_names.count.should == 1
  end

  it 'should bulk delete upload directories' do
    @page.directories[1].find('input[type="checkbox"]').set true
    @page.directories[2].find('input[type="checkbox"]').set true
    @page.bulk_action.select 'Remove'
    @page.action_submit_button.click
    @page.wait_until_modal_visible
    @page.modal.should have_text 'Upload directory: Main Upload Directory'
    @page.modal.should have_text 'Upload directory: About'
    @page.modal_submit_button.click
    no_php_js_errors

    @page.should have_alert
    @page.alert[:class].should include 'success'
    @page.alert.text.should include 'Upload directories removed'
    @page.alert.text.should include '2 upload directories were removed.'
    @page.directory_names.count.should == 0
  end
end