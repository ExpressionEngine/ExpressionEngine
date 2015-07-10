class EditFile < FileManagerPage
	set_url_matcher /files\/file\/edit/

	# Main box elements
	element :heading, 'div.col.w-12 div.box h1'
	element :crop_button, 'div.col.w-12 div.box h1 a.action'

	# Edit form
	element :title_input, 'div.col.w-12 div.box form fieldset input[name="title"]'
	element :description_input, 'div.col.w-12 div.box form fieldset textarea[name="description"]'
	element :credit_input, 'div.col.w-12 div.box form fieldset input[name="credit"]'
	element :location_input, 'div.col.w-12 div.box form fieldset input[name="location"]'
	element :form_submit_button, 'div.col.w-12 div.box form fieldset.form-ctrls input[type="submit"]'

	def load
		click_link 'Files'
		find('div.box form div.tbl-wrap table tr:nth-child(2) td:nth-child(4) ul.toolbar li.edit').click
	end

end