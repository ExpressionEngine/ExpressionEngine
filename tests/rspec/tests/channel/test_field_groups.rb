require './bootstrap.rb'

feature 'Field Groups' do
  before(:each) do
    cp_session
    @page = FieldGroups.new
    @page.load
    no_php_js_errors
  end

  it 'has two field groups' do
    @page.all_there?.should == true

    @page.field_groups.should have(2).items
    @page.field_groups_edit.should have(2).items
    @page.field_groups_fields.should have(2).items
  end

  context 'when creating or editing field groups' do
    def save_field_group(number_of)
      name = 'Test Group 1'
      field_group_form = FieldGroupForm.new
      field_group_form.name.set name
      field_group_form.submit[0].click

      @page.load

      @page.field_groups.should have(number_of).items
      @page.field_groups_edit.should have(number_of).items
      @page.field_groups_fields.should have(number_of).items

      @page.field_groups.any? { |fg| fg.text.include?(name) }.should == true
    end

    it 'creates a field group' do
      @page.create_new.click
      save_field_group(3)
    end

    it 'saves the field group name' do
      @page.field_groups_edit[0].click
      save_field_group(2)
    end
  end

  it 'deletes a field group' do
    @page.field_groups[0].find('li.remove a').click
    @page.wait_until_modal_visible
    @page.modal.should have_text 'Field Group: '
    @page.modal_submit_button.click
    no_php_js_errors

    @page.should have_alert
    @page.alert[:class].should include 'success'
    @page.field_groups.should have(1).items
  end
end
