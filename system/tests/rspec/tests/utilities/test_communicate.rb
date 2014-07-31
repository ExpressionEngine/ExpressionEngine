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

	it "shows errors when required fields are not populated" do
		@page.from_email.set ''
		@page.submit_button.click

		@page.should have_alert
		@page.should have_css 'div.alert.issue'
		@page.alert.should have_text "An error occurred"

		@page.subject.first(:xpath, ".//../..")[:class].should include 'invalid'
		@page.subject.first(:xpath, ".//..").should have_css 'em.ee-form-error-message'
		@page.subject.first(:xpath, ".//..").should have_text 'field is required.'

		@page.body.first(:xpath, ".//../..")[:class].should include 'invalid'
		@page.body.first(:xpath, ".//..").should have_css 'em.ee-form-error-message'
		@page.body.first(:xpath, ".//..").should have_text 'field is required.'

		@page.from_email.first(:xpath, ".//../..")[:class].should include 'invalid'
		@page.from_email.first(:xpath, ".//..").should have_css 'em.ee-form-error-message'
		@page.from_email.first(:xpath, ".//..").should have_text 'field is required.'

		@page.recipient.first(:xpath, ".//../..")[:class].should include 'invalid'
		@page.recipient.first(:xpath, ".//..").should have_css 'em.ee-form-error-message'
		@page.recipient.first(:xpath, ".//..").should have_text 'You left some fields empty.'

		@page.submit_button[:value].should eq 'Fix Errors, Please'
	end
end