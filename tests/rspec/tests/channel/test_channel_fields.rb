require './bootstrap.rb'

feature 'Channel Fields' do
  before(:each) do
    cp_session
    @page = ChannelFields.new
    @page.load
    no_php_js_errors
  end

  it 'has three fields' do
    @page.all_there?.should == true

    @page.fields.should have(3).items
    @page.fields_edit.should have(3).items
    @page.fields_checkboxes.should have(3).items
  end

  context 'when creating or editing fields' do
    def save_field
      form = ChannelFieldForm.new
      form.all_there?.should == true
      form.field_type.select 'Date'
      form.field_label.set 'Date'
      form.field_name.set 'date'
      form.submit

      @page.alert.has_content?('The field Date has been').should == true

      @page.fields.any? { |f| f.text.include?('Date') }.should == true
      @page.fields.any? { |f| f.text.include?('{date}') }.should == true
    end

    it 'creates a field' do
      @page.create_new.click
      save_field
    end

    it 'saves a field' do
      @page.fields_edit[1].click
      save_field
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

    @page.fields.should have(2).items
    @Page.field_group[0].text.should include 'Body'
    @Page.field_group[1].text.should include 'News Image'
  end
end
