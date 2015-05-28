class MemberSettings < ControlPanelPage

	element :allow_member_registration_y, 'input[name=allow_member_registration][value=y]'
	element :allow_member_registration_n, 'input[name=allow_member_registration][value=n]'
	element :req_mbr_activation, 'select[name=req_mbr_activation]'
	element :require_terms_of_service_y, 'input[name=require_terms_of_service][value=y]'
	element :require_terms_of_service_n, 'input[name=require_terms_of_service][value=n]'
	element :allow_member_localization_y, 'input[name=allow_member_localization][value=y]'
	element :allow_member_localization_n, 'input[name=allow_member_localization][value=n]'
	element :default_member_group, 'select[name=default_member_group]'
	element :member_theme, 'select[name=member_theme]'
	element :memberlist_order_by, 'select[name=memberlist_order_by]'
	element :memberlist_sort_order, 'select[name=memberlist_sort_order]'
	element :memberlist_row_limit, 'select[name=memberlist_row_limit]'
	element :new_member_notification_y, 'input[name=new_member_notification][value=y]'
	element :new_member_notification_n, 'input[name=new_member_notification][value=n]'
	element :mbr_notification_emails, 'input[name=mbr_notification_emails]'

	def load
		settings_btn.click
		within 'div.sidebar' do
			click_link 'Members'
		end
	end
end