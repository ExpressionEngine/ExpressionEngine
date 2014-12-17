class UploadDirectories < ControlPanelPage

	element :table, 'table'
	element :sort_col, 'table th.highlight'
	elements :directories, 'table tr'
	elements :directory_names, 'table tr td:nth-child(2)'
	
	element :bulk_action, 'form fieldset.tbl-bulk-act select[name="bulk_action"]'
	element :action_submit_button, 'form fieldset.tbl-bulk-act input.submit'

	def load
		settings_btn.click
		within 'div.sidebar' do
			click_link 'Upload Directories'
		end
	end
end