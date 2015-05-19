require './bootstrap.rb'

feature 'Upload Directory Sync' do

  before(:all) do
    # Paths we'll need on hand
    @base_path = File.expand_path('support/file-sync')
    @upload_path = @base_path + '/uploads/'
    @thumbs_path = @upload_path + '_thumbs/'
    @images_path = @base_path + '/images/' # Files should sync without error
    @non_images_path = @base_path + '/non-images/' # Files should sync without error
    @bad_files = @base_path + '/bad/' # Should cause errors

    $images_count = Dir.glob(@images_path + '*').count
    $non_images_count = Dir.glob(@non_images_path + '*').count
    $bad_files_count = Dir.glob(@bad_files + '*').count

    FileUtils.cp_r @images_path + '.', @upload_path
  end

  before(:each) do

    cp_session

    # Create a new upload directory for testing
    new_upload = UploadEdit.new
    new_upload.load
    new_upload.name.set 'Dir'
    new_upload.url.set 'http://ee/'
    new_upload.server_path.set @upload_path
    new_upload.submit
    new_upload.should have_text 'Upload directory saved'

    @page = UploadSync.new
    @page.load_sync_for_dir(3)
    no_php_js_errors
  end

  def get_files
    files = []
    $db.query('SELECT file_name FROM exp_files').each(:as => :array) do |row|
      files << row[0]
    end
    clear_db_result

    return files
  end

  it 'shows the Upload Directory Sync page' do
    @page.should have_progress_bar
    @page.should have_no_sizes # No image manipulations yet
  end

  it 'should sync the directory' do
    @page.should have_text $images_count.to_s + ' image files'

    @page.submit

    no_php_js_errors

    # Make sure progress bar progressed in the proper increments
    progress_bar_values = @page.log_progress_bar_moves
    progress_bar_values.should == @page.progress_bar_moves_for_file_count($images_count)

    no_php_js_errors

    # Make sure thumbnails were created for each file
    thumb_count = Dir.glob(@thumbs_path + '*').count - 1 # - 1 for index.html
    thumb_count.should == $images_count

    # Get a list of files from the database AND the filesystem
    pwd = Dir.getwd
    Dir.chdir(@images_path)
    db_files = get_files
    files = Dir.glob('*')
    Dir.chdir(pwd)

    # Make sure files made it into the database
    db_files.should include *files

    @page.alert.should have_text 'Upload directory synchronized'
  end

  # Helper that creates a couple image manipulations for us
  def create_manipulation
    new_upload = UploadEdit.new
    new_upload.load_edit_for_dir(3)

    new_upload.grid_add_no_results.click
    new_upload.name_for_row(1).set 'some_name'
    new_upload.width_for_row(1).set '20'
    new_upload.height_for_row(1).set '30'

    new_upload.grid_add.click
    new_upload.name_for_row(2).set 'some_other_name'
    new_upload.resize_type_for_row(2).select 'Crop (part of image)'
    new_upload.width_for_row(2).set '50'
    new_upload.height_for_row(2).set '40'

    no_php_js_errors

    new_upload.submit
    new_upload.should have_text 'Upload directory saved'

    no_php_js_errors
  end

  it 'should apply image manipulations to new files' do
    # First we need to create the manipulations
    create_manipulation

    @page.load_sync_for_dir(3)
    @page.should have_sizes
    @page.should have_text 'some_name Constrain, 20px by 30px'
    @page.should have_text 'some_other_name Crop, 50px by 40px'
    # Should be unchecked by default
    @page.sizes.count.should == 2
    @page.sizes[0].checked?.should == false
    @page.sizes[1].checked?.should == false

    @page.submit

    no_php_js_errors

    # Make sure progress bar progressed in the proper increments
    progress_bar_values = @page.log_progress_bar_moves
    progress_bar_values.should == @page.progress_bar_moves_for_file_count($images_count)

    no_php_js_errors

    constrain_files = Dir.glob(@upload_path + '_some_name/*');
    crop_files = Dir.glob(@upload_path + '_some_other_name/*');

    # Make sure thumbnails and image manupulations were created for each file
    thumb_count = Dir.glob(@thumbs_path + '*').count - 1 # - 1 for index.html
    thumb_count.should == $images_count
    thumb_count = constrain_files.count - 1 # - 1 for index.html
    thumb_count.should == $images_count
    thumb_count = crop_files.count - 1 # - 1 for index.html
    thumb_count.should == $images_count

    # Next, we'll make sure the manipulations were properly constrained/cropped
    valid_extensions = ['.gif', '.jpg', '.JPG', '.jpeg', '.png']

    for file in constrain_files
        if valid_extensions.include? File.extname(file) then
            fh = File.open(file, 'rb')
            size = ImageSize.new(fh.read)
            size.w.should <= 20
            size.h.should <= 30
        end
    end

    for file in crop_files
        if valid_extensions.include? File.extname(file) then
            fh = File.open(file, 'rb')
            size = ImageSize.new(fh.read)
            size.w.should == 50
            size.h.should == 40
        end
    end
  end

  it 'should not overwrite existing manipulations if not told to' do
    create_manipulation

    @page.load_sync_for_dir(3)
    @page.submit
    @page.wait_for_alert
    no_php_js_errors

    file = Dir.glob(@upload_path + '_some_name/*.png').first
    mtime = File.mtime(file)

    # Sleep for a second to make sure enough time has passed for the file
    # modified time to be different if it gets modified
    sleep 1

    # Submit again with no manipulations checked
    @page.submit
    @page.wait_for_alert

    # Modification time should be the exact same
    mtime.should == File.mtime(file)

  end

  it 'should overwrite existing manipulations if told to' do
    create_manipulation

    @page.load_sync_for_dir(3)
    @page.submit
    @page.wait_for_alert
    no_php_js_errors

    file = Dir.glob(@upload_path + '_some_name/*.png').first
    mtime = File.mtime(file)

    # Sleep for a second to make sure enough time has passed for the file
    # modified time to be different
    sleep 1

    # Submit again with manipulations checked
    @page.sizes[0].click
    @page.sizes[1].click
    @page.submit
    @page.wait_for_alert

    # Modification time should be different as the file was overwritten
    mtime.should_not == File.mtime(file)

  end

  it 'should not sync non-images if directory does not allow it' do
    # Copy some non-image files over
    FileUtils.cp_r @non_images_path + '.', @upload_path

    # Page should still only report the number of images and sync successfully
    @page.should have_text $images_count.to_s + ' image files'

    @page.submit
    @page.wait_for_alert
    @page.alert.should have_text 'Upload directory synchronized'
  end

  it 'should sync non-images if directory allows it' do
    FileUtils.cp_r @non_images_path + '.', @upload_path

    file_count = $images_count + $non_images_count

    new_upload = UploadEdit.new
    new_upload.load_edit_for_dir(3)
    new_upload.allowed_types.select 'All file types'
    new_upload.submit
    new_upload.should have_text 'Upload directory saved'
    no_php_js_errors

    @page.load_sync_for_dir(3)

    # Page should show file count for all files now
    @page.should have_text file_count.to_s + ' files'

    @page.submit
    @page.wait_for_alert
    @page.alert.should have_text 'Upload directory synchronized'

    FileUtils.rm_rf Dir.glob(@upload_path + '**/*.mp3')
  end

  it 'should not sync invalid mimes' do
    FileUtils.cp_r @bad_files + '.', @upload_path

    file_count = $images_count# + $bad_files_count

    new_upload = UploadEdit.new
    new_upload.load_edit_for_dir(3)
    new_upload.allowed_types.select 'All file types'
    new_upload.submit
    new_upload.should have_text 'Upload directory saved'
    no_php_js_errors

    @page.load_sync_for_dir(3)

    # Page should show file count for all files now
    @page.should have_text file_count.to_s + ' files'

    @page.submit
    @page.wait_for_alert
    @page.alert.should have_text 'Upload directory synchronized'
    # @page.alert.should have_text 'Some files could not be synchronized'
    # @page.alert.should have_text 'script copy 2.sh: Invalid mime type, file could not be processed script copy 3.sh: Invalid mime type, file could not be processed script copy 4.sh: Invalid mime type, file could not be processed script copy.sh: Invalid mime type, file could not be processed script.sh: Invalid mime type, file could not be processed'
  end

  after(:all) do
    FileUtils.rm_rf Dir.glob(@upload_path + '**')
  end

end