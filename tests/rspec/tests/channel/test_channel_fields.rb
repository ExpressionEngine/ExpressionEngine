require './bootstrap.rb'

feature 'Channel Fields' do
  before(:each) do
    cp_session
    @page = ChannelFields.new
    @page.load
    no_php_js_errors
  end

  it 'has seven fields' do
    @page.all_there?.should == true

    @page.fields.should have(7).items
    @page.fields_edit.should have(7).items
    @page.fields_checkboxes.should have(7).items
  end

  context 'when creating or editing fields' do
    def save_field
      form = ChannelFieldForm.new
      form.create_field(
        type: 'Text Input',
        label: 'Shipping Method'
      )
      form.all_there?.should == true

      @page.should have_alert
      @page.should have_alert_success
    end

    it 'creates a field' do
      save_field
    end

    it 'saves a field' do
      @page.fields_edit[1].click
      @page.submit

      @page.should have_alert
      @page.should have_alert_success
    end

    it 'invalidates reserved words used in field_name' do
      form = ChannelFieldForm.new
      form.create_field(
        type: 'Date',
        label: 'Date'
      )
      form.all_there?.should == true

      @page.alert.has_content?('Cannot Create Field').should == true
    end
  end

  it 'deletes a field' do
    @page.fields_checkboxes[1].click
    @page.wait_for_bulk_action

    @page.has_bulk_action?.should == true
    @page.has_action_submit_button?.should == true

    @page.bulk_action.select 'Remove'
    @page.action_submit_button.click

    @page.wait_for_modal_submit_button
    @page.modal_submit_button.click

    @page.should have_alert
    @page.should have_alert_success
    @page.fields.should have(6).items
  end
end
