class UploadEdit < ControlPanelPage

	element :name, 'input[name=name]'
	element :url, 'input[name=url]'
	element :server_path, 'input[name=server_path]'
	element :allowed_types, 'select[name=allowed_types]'
	element :max_size, 'input[name=max_size]'
	element :max_width, 'input[name=max_width]'
	element :max_height, 'input[name=max_height]'
	element :image_manipulations, '#image_manipulations'
	elements :grid_rows, '#image_manipulations tr'
	element :grid_add_no_results, 'tr.no-results a.btn'
	element :grid_add, 'ul.toolbar li.add a'
	elements :upload_member_groups, 'input[name="upload_member_groups[]"]'
	elements :cat_group, 'input[name="cat_group[]"]'

	def load
		settings_btn.click
		within 'div.sidebar' do
			click_link 'Upload Directories'
		end
		click_link 'Create New'
	end

	# Dynamic getter for a specific Grid row
	def grid_row(row)
		# Plus three to skip over header, blank row and no results row
		image_manipulations.find('tr:nth-child('+(row+3).to_s+')')
	end

	# Returns the name field in a specific Grid row, and so on...
	def name_for_row(row)
		self.grid_row(row).find('td:first-child input')
	end

	def resize_type_for_row(row)
		self.grid_row(row).find('td:nth-child(2) select')
	end

	def width_for_row(row)
		self.grid_row(row).find('td:nth-child(3) input')
	end

	def height_for_row(row)
		self.grid_row(row).find('td:nth-child(4) input')
	end

	def delete_for_row(row)
		self.grid_row(row).find('td:nth-child(5) li.remove a')
	end
end