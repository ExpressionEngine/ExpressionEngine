require './bootstrap.rb'

feature 'Word Censorship Settings' do

  before(:each) do
    cp_session
    @page = WordCensorship.new
    @page.load
    no_php_js_errors
  end

  it 'shows the Word Censorship Settings page' do
    @page.all_there?.should == true
  end

  it 'should load current settings into form fields' do
    enable_censoring = ee_config(item: 'enable_censoring')
    censor_replacement = ee_config(item: 'censor_replacement')
    censored_words = ee_config(item: 'censored_words')

    @page.enable_censoring_y.checked?.should == (enable_censoring == 'y')
    @page.enable_censoring_n.checked?.should == (enable_censoring == 'n')
    @page.censor_replacement.value.should == ee_config(item: 'censor_replacement')
    @page.censored_words.value.should == ee_config(item: 'censored_words').gsub('|', "\n")
  end

  it 'should reject XSS' do
    @page.censor_replacement.set $xss_vector
    @page.submit
    
    should_have_error_text(@page.censor_replacement, $xss_error)
    should_have_form_errors(@page)
  end

  it 'should save and load the settings' do
    @page.enable_censoring_y.click
    @page.censor_replacement.set '####'
    @page.censored_words.set "Poop\nPerl"
    @page.submit

    @page.should have_text 'Preferences updated'
    @page.enable_censoring_y.checked?.should == true
    @page.censor_replacement.value.should == '####'
    @page.censored_words.value.should == "Poop\nPerl"

    enable_censoring = ee_config(item: 'enable_censoring')
    @page.enable_censoring_y.checked?.should == (enable_censoring == 'y')
    @page.enable_censoring_n.checked?.should == (enable_censoring == 'n')
    @page.censor_replacement.value.should == ee_config(item: 'censor_replacement')
    @page.censored_words.value.should == ee_config(item: 'censored_words').gsub('|', "\n")
  end
end