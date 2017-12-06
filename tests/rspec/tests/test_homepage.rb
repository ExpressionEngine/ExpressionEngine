require './bootstrap.rb'

feature 'Homepage' do
  before :each do
    cp_session
    @page = Homepage.new
    @page.load
    no_php_js_errors
  end

  context 'when spam module is not installed' do
    before :each do
      toggle_spam :off
    end

    it 'does not show flagged comments' do
      @page.comment_info.text.should_not include "flagged as potential spam"
    end
  end

  context 'when spam module is installed' do
    before :each do
      toggle_spam :on
    end

    it 'shows flagged comments' do
      @page.comment_info.text.should include "flagged as potential spam"
    end
  end

  def toggle_spam(state)
    @page.open_dev_menu
    click_link 'Add-Ons'

    can_install = @page.has_selector?('a[data-post-url*="cp/addons/install/spam"]')

    if state == :on && can_install
      find('a[data-post-url*="cp/addons/install/spam"]').click
    elsif state == :off && can_install == false
      find('input[value="spam"]').click
      find('select[name="bulk_action"]').set 'remove'
      find('.tbl-bulk-act button').click
      sleep 1
      find('.modal form input.btn[type="submit"]').click
    end

    @page.load
  end
end
