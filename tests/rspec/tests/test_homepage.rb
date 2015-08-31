require './bootstrap.rb'

feature 'Homepage' do
  before :all do
  end

  before :each do
    cp_session
    @page = Homepage.new
    @page.load
    no_php_js_errors
  end

  after :all do
  end

  context 'when spam module is not installed' do
    before :each do
      toggle_spam(false)
    end

    it 'does not show flagged comments' do
      @page.comment_info.text.should_not include "flagged as potential spam"
    end
  end

  context 'when spam module is installed' do
    before :each do
      toggle_spam(true)
    end

    it 'shows flagged comments' do
      @page.comment_info.text.should include "flagged as potential spam"
    end
  end

  def toggle_spam(enabled)
    @page.open_dev_menu
    click_link 'Add-On Manager'

    if enabled && @page.has_selector?('a[href*="cp/addons/install/spam"]')
      find('a[href*="cp/addons/install/spam"]').click
    elsif @page.has_selector?('a[href*="cp/addons/install/spam"]') == false
      find('input[value="spam"]').click
      find('select[name="bulk_action"]').set 'remove'
      find('.tbl-bulk-act button').click
      sleep 1
      find('.modal form input.btn[type="submit"]').click
    end

    @page.load
  end
end
