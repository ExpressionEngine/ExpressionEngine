require './bootstrap.rb'

feature 'Communicate' do

	before(:each) do
		cp_session
		@page = Communicate.new
		@page.load

		@page.should be_displayed
		@page.title.text.should eq 'Communicate ✱ Required Fields'
		@page.should have_subject
		@page.should have_body
		@page.should have_mailtype
		@page.should have_wordwrap
		@page.should have_from_email
		@page.should have_attachment
		@page.should have_recipient
		@page.should have_cc
		@page.should have_bcc
		@page.should have_member_groups
		@page.should have_submit_button
	end

	it "shows the Communicate page" do
		@page.mailtype.value.should eq 'text'
		@page.wordwrap.checked?.should eq true
	end

end