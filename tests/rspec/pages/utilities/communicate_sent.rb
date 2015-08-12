class CommunicateSent < ControlPanelPage
	set_url_matcher /utilities\/communicate\/sent/

	element :heading, 'div.w-12 div.box h1'

	element :phrase_search, 'input[name=search]'
	element :search_submit_button, 'form fieldset.tbl-search input.submit'

	element :email_table, 'div.box div.tbl-ctrls form div.tbl-wrap table'
	element :no_results, 'div.box div.tbl-ctrls form div.tbl-wrap table tr.no-results'
	element :subject_header, 'div.box div.tbl-ctrls form div.tbl-wrap table tr th:first-child'
	element :date_header, 'div.box div.tbl-ctrls form div.tbl-wrap table tr th:nth-child(2)'
	element :total_sent_header, 'div.box div.tbl-ctrls form div.tbl-wrap table tr th:nth-child(3)'
	elements :rows, 'div.box div.tbl-ctrls form div.tbl-wrap table tr'
	elements :subjects, 'div.box div.tbl-ctrls form div.tbl-wrap table tr td:first-child'
	elements :dates, 'div.box div.tbl-ctrls form div.tbl-wrap table tr td:nth-child(2)'
	elements :total_sents, 'div.box div.tbl-ctrls form div.tbl-wrap table tr td:nth-child(3)'

	element :bulk_action, 'form fieldset.tbl-bulk-act select[name="bulk_action"]'
	element :action_submit_button, 'form fieldset.tbl-bulk-act button.submit'

	def generate_data(
		count: 250,
		timestamp: nil,
		timestamp_min: nil,
		timestamp_max: nil,
		from_name: nil,
		from_email: nil,
		recipient: nil,
		cc: nil,
		bcc: nil,
		subject: nil,
		message: nil,
		total_sent: nil
		)
		command = "cd fixtures && php emailCache.php"

		if count
			command += " --count " + count.to_s
		end

		if timestamp
			command += " --timestamp " + timestamp.to_s
		end

		if timestamp_min
			command += " --timestamp-min " + timestamp_min.to_s
		end

		if timestamp_max
			command += " --timestamp-max " + timestamp_max.to_s
		end

		if from_name
			command += " --from-name '" + from_name.to_s + "'"
		end

		if from_email
			command += " --from-email '" + from_email.to_s + "'"
		end

		if recipient
			command += " --recipient '" + recipient.to_s + "'"
		end

		if cc
			command += " --cc '" + cc.to_s + "'"
		end

		if bcc
			command += " --bcc '" + bcc.to_s + "'"
		end

		if subject
			command += " --subject '" + subject.to_s + "'"
		end

		if message
			command += " --message '" + message.to_s + "'"
		end

		if total_sent
			command += " --total-sent '" + total_sent.to_s + "'"
		end

		command += " > /dev/null 2>&1"

		system(command)
	end

	def load
		self.open_dev_menu
		click_link 'Utilities'
		click_link 'Sent'
	end
end
