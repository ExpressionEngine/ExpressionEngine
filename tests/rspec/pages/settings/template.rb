class TemplateSettings < ControlPanelPage

	element :strict_urls_y, 'input[name=strict_urls][value=y]'
	element :strict_urls_n, 'input[name=strict_urls][value=n]'
	element :site_404, 'select[name=site_404]'
	element :save_tmpl_revisions_y, 'input[name=save_tmpl_revisions][value=y]'
	element :save_tmpl_revisions_n, 'input[name=save_tmpl_revisions][value=n]'
	element :max_tmpl_revisions, 'input[name=max_tmpl_revisions]'
	element :save_tmpl_files_y, 'input[name=save_tmpl_files][value=y]'
	element :save_tmpl_files_n, 'input[name=save_tmpl_files][value=n]'
	element :tmpl_file_basepath, 'input[name=tmpl_file_basepath]'

	def load
		settings_btn.click
		click_link 'Template Settings'
	end
end