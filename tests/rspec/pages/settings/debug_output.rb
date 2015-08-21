class DebugOutput < ControlPanelPage

	# This is ridiculous
	element :debug_y, 'input[name=debug][value="1"]'
	element :debug_n, 'input[name=debug][value="0"]'
	element :show_profiler_y, 'input[name=show_profiler][value=y]'
	element :show_profiler_n, 'input[name=show_profiler][value=n]'
	element :gzip_output_y, 'input[name=gzip_output][value=y]'
	element :gzip_output_n, 'input[name=gzip_output][value=n]'
	element :force_query_string_y, 'input[name=force_query_string][value=y]'
	element :force_query_string_n, 'input[name=force_query_string][value=n]'
	element :send_headers_y, 'input[name=send_headers][value=y]'
	element :send_headers_n, 'input[name=send_headers][value=n]'

	element :redirect_method, 'select[name=redirect_method]'
	element :cache_driver, 'select[name=cache_driver]'
	element :max_caches, 'input[name=max_caches]'

	def load
		settings_btn.click
		click_link 'Debugging & Output'
	end
end