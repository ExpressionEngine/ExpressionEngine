class UploadFile < FileManagerPage
	set_url_matcher /files\/upload/

	# Main box elements
	element :heading, 'div.col.w-12 div.box h1'

	# Edit form
	element :file_input, 'div.col.w-12 div.box form fieldset input[name="file"]'
	element :title_input, 'div.col.w-12 div.box form fieldset input[name="title"]'
	element :description_input, 'div.col.w-12 div.box form fieldset textarea[name="description"]'
	element :credit_input, 'div.col.w-12 div.box form fieldset input[name="credit"]'
	element :location_input, 'div.col.w-12 div.box form fieldset input[name="location"]'
	element :form_submit_button, 'div.col.w-12 div.box form fieldset.form-ctrls input[type="submit"]'

	def load
		click_link 'Files'
		click_link 'Main Upload Directory'
		click_link 'Upload New File'
	end

end