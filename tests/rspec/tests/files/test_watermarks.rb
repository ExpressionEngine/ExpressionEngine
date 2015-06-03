require './bootstrap.rb'

feature 'Watermark Create/Edit' do

  before(:each) do
    cp_session
    @page = WatermarkEdit.new
    @page.load
    no_php_js_errors
  end

  it 'shows the Watermark Create/Edit page' do
    @page.all_there?.should == true
    @page.should have_text 'Create Watermark'
  end

  it 'should validate fields' do
    @page.submit

    no_php_js_errors
    should_have_form_errors(@page)
    @page.should have_text 'Attention: Watermark not saved'
    should_have_error_text(@page.wm_name, $required_error)

    # AJAX validation
    # Required name
    @page.load
    @page.wm_name.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.wm_name, $required_error)
    should_have_form_errors(@page)

    # Natural numbers
    @page.wm_padding.set 'sdfsd'
    @page.wm_padding.trigger 'blur'
    @page.wait_for_error_message_count(2)
    should_have_error_text(@page.wm_padding, $natural_number)
    should_have_form_errors(@page)

    # Do these offset fields one at a time, AJAX validation is currently
    # confused about having an error message depend on two fields,
    # regular validation should take care of it though
    @page.wm_hor_offset.set 'sdfsd'
    @page.wm_hor_offset.trigger 'blur'
    @page.wait_for_error_message_count(3)
    should_have_error_text(@page.wm_hor_offset, $integer_error)
    should_have_form_errors(@page)

    @page.wm_hor_offset.set '0'
    @page.wm_hor_offset.trigger 'blur'
    @page.wait_for_error_message_count(2)
    should_have_no_error_text(@page.wm_hor_offset)
    should_have_form_errors(@page)

    @page.wm_vrt_offset.set 'sdfsd'
    @page.wm_vrt_offset.trigger 'blur'
    @page.wait_for_error_message_count(3)
    should_have_error_text(@page.wm_vrt_offset, $integer_error)
    should_have_form_errors(@page)

    # Lots of AJAX going on, make sure there are no JS errors
    no_php_js_errors

    #@page.submit
    #no_php_js_errors
    #@page.name.value.should == 'Dir'
    #@page.url.value.should == 'http://ee3/'
    #@page.server_path.value.should == @upload_path + '/'
  end

end