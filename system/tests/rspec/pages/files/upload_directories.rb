class UploadDirectories < ControlPanelPage

	element :table, 'table'
	elements :directories, 'table tr'
	elements :directory_names, 'table tr td:nth-child(2)'

	def load
		click_link 'Files'
		within 'div.sidebar' do
			click_link 'Upload Directories'
		end
	end
end