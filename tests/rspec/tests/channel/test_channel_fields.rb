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
      form.all_there?.should == true
      form.create_field(
        type: 'Text Input',
        label: 'Shipping Method'
      )

      @page.should have_alert
      @page.alert[:class].should include 'success'
    end

    it 'creates a field' do
      @page.create_new.click
      save_field
    end

    it 'saves a field' do
      @page.fields_edit[1].click
      save_field
    end

    it 'invalidates reserved words used in field_name' do
      @page.create_new.click
      form = ChannelFieldForm.new
      form.all_there?.should == true
      form.create_field(
        type: 'Date',
        label: 'Date'
      )

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
    @page.alert[:class].should include 'success'
    @page.fields.should have(6).items
  end
end
