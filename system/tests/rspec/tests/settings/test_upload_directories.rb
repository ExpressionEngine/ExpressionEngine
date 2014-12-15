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

    @page.directories.map {|source| source.text}.should == directories
    @page.should have(directories.count).directories
  end

  it 'should sort the list of upload directories' do
    @page.sort_col.text.should eq 'ID#'
    @page.sort_links[0].click

    directories = get_directories

    @page.directories.map {|source| source.text}.should == directories.reverse
    @page.should have(directories.count).directories
    @page.sort_col.text.should eq 'ID#'

    # Sort alphabetically
    @page.sort_links[1].click
    @page.directories.map {|source| source.text}.should == directories.sort
    @page.sort_col.text.should eq 'Directory name'

    # Sort reverse alphabetically
    @page.sort_links[1].click
    @page.directories.map {|source| source.text}.should == directories.sort { |x,y| y <=> x }
    @page.sort_col.text.should eq 'Directory name'
  end

end