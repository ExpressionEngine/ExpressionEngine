<?php extend_template('default') ?>

<?php
	echo form_open('C=admin_content'.AMP.'M=channel_edit', array('id'=>'channel_prefs'), $form_hidden);
	$this->table->set_template($cp_pad_table_template);
	$this->table->template['thead_open'] = '<thead class="visualEscapism">';

	// Create the Channel Base Setup
	$this->table->set_caption(lang('channel_base_setup'));
	$this->table->set_heading(lang('preference'), lang('setting'));

	$preference = required().lang('channel_title', 'channel_title').form_error('channel_title');
	$controls = form_input(array('id'=>'channel_title','name'=>'channel_title','class'=>'fullfield', 'value'=>set_value('channel_title', $channel_title)));
	$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

	$preference = required().lang('channel_name', 'channel_name').form_error('channel_name');
	$controls = form_input(array('id'=>'channel_name','name'=>'channel_name','class'=>'fullfield', 'value'=>set_value('channel_name', $channel_name)));
	$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

	$preference = lang('channel_description', 'channel_description');
	$controls = form_input(array('id'=>'channel_description','name'=>'channel_description','class'=>'fullfield', 'value'=>set_value('channel_description', $channel_description)));
	$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

	$preference = lang('channel_lang', 'channel_lang');
	$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>form_dropdown('channel_lang', $languages, $channel_lang)));

	echo $this->table->generate();
	$this->table->clear(); // Clear out for the next one

	// -------------------------------------------------------------
	// Create the Paths table
	$this->table->set_caption(lang('paths'));
	$this->table->set_heading(lang('preference'), lang('setting'));

	$preference = lang('channel_url', 'channel_url').'<br />'.lang('channel_url_exp');
	$controls = form_input(array('id'=>'channel_url','name'=>'channel_url','class'=>'fullfield', 'value'=>set_value('channel_url', $channel_url)));
	$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

	$preference = lang('comment_url', 'comment_url').'<br />'.lang('comment_url_exp');
	$controls = form_input(array('id'=>'comment_url','name'=>'comment_url','class'=>'fullfield', 'value'=>set_value('comment_url', $comment_url)));
	$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

	$preference = lang('search_results_url', 'search_results_url').'<br />'.lang('search_results_url_exp');
	$controls = form_input(array('id'=>'search_results_url','name'=>'search_results_url','class'=>'fullfield', 'value'=>set_value('search_results_url', $search_results_url)));
	$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

	$preference = lang('rss_url', 'rss_url').'<br />'.lang('rss_url_exp');
	$controls = form_input(array('id'=>'rss_url','name'=>'rss_url','class'=>'fullfield', 'value'=>set_value('rss_url', $rss_url)));
	$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

	$preference = lang('live_look_template', 'live_look_template');
	$controls = form_dropdown('live_look_template', $live_look_template_options, $live_look_template, 'id="live_look_template"');
	$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

	echo $this->table->generate();
	$this->table->clear(); // Clear out for the next one

	// -------------------------------------------------------------
	// Create the default settings / Administrative Preferences table
	$this->table->set_caption(lang('default_settings'));
	$this->table->set_heading(lang('preference'), lang('setting'));

	$preference = lang('deft_status', 'deft_status');
	$controls = form_dropdown('deft_status', $deft_status_options, $deft_status, 'id="deft_status"');
	$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

	$preference = lang('deft_category', 'deft_category');
	$controls = form_dropdown('deft_category', $deft_category_options, $deft_category, 'id="deft_category"');
	$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

	$preference = lang('deft_comments', 'deft_comments');
	$controls = form_radio(array('name'=>'deft_comments', 'id'=>'deft_comments_y', 'value'=>'y', 'checked'=>($deft_comments == 'y') ? TRUE : FALSE)).NBS.lang('yes', 'deft_comments_y').NBS.NBS.NBS.NBS.NBS;
	$controls .= form_radio(array('name'=>'deft_comments', 'id'=>'deft_comments_n', 'value'=>'n', 'checked'=>($deft_comments == 'n') ? TRUE : FALSE)).NBS.lang('no', 'deft_comments_n');
	$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

	$preference = lang('search_excerpt', 'search_excerpt');
	$controls = form_dropdown('search_excerpt', $search_excerpt_options, $search_excerpt, 'id="search_excerpt"');
	$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

	echo $this->table->generate();
	$this->table->clear(); // Clear out for the next one

	// -------------------------------------------------------------
	// Create the Channel Posting Preferences table
	$this->table->set_caption(lang('channel_settings'));
	$this->table->set_heading(lang('preference'), lang('setting'));

	$preference = lang('channel_html_formatting', 'channel_html_formatting');
	$controls = form_dropdown('channel_html_formatting', $channel_html_formatting_options, $channel_html_formatting, 'id="channel_html_formatting"');
	$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

	$preference = lang('channel_allow_img_urls', 'channel_allow_img_urls');
	$controls = form_radio(array('name'=>'channel_allow_img_urls', 'id'=>'channel_allow_img_urls_y', 'value'=>'y', 'checked'=>($channel_allow_img_urls == 'y') ? TRUE : FALSE)).NBS.lang('yes', 'channel_allow_img_urls_y').NBS.NBS.NBS.NBS.NBS;
	$controls .= form_radio(array('name'=>'channel_allow_img_urls', 'id'=>'channel_allow_img_urls_n', 'value'=>'n', 'checked'=>($channel_allow_img_urls == 'n') ? TRUE : FALSE)).NBS.lang('no', 'channel_allow_img_urls_n');
	$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

	$preference = lang('auto_link_urls', 'channel_auto_link_urls');
	$controls = form_radio(array('name'=>'channel_auto_link_urls', 'id'=>'channel_auto_link_urls_y', 'value'=>'y', 'checked'=>($channel_auto_link_urls == 'y') ? TRUE : FALSE)).NBS.lang('yes', 'channel_auto_link_urls_y').NBS.NBS.NBS.NBS.NBS;
	$controls .= form_radio(array('name'=>'channel_auto_link_urls', 'id'=>'channel_auto_link_urls_n', 'value'=>'n', 'checked'=>($channel_auto_link_urls == 'n') ? TRUE : FALSE)).NBS.lang('no', 'channel_auto_link_urls_n');
	$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

	echo $this->table->generate();
	$this->table->clear(); // Clear out for the next one

	// -------------------------------------------------------------
	// Create the Versioning Preferences table
	$this->table->set_caption(lang('versioning'));
	$this->table->set_heading(lang('preference'), lang('setting'));

	$preference = lang('enable_versioning', 'enable_versioning');
	$controls = form_radio(array('name'=>'enable_versioning', 'id'=>'enable_versioning_y', 'value'=>'y', 'checked'=>($enable_versioning == 'y') ? TRUE : FALSE)).NBS.lang('yes', 'enable_versioning_y').NBS.NBS.NBS.NBS.NBS;
	$controls .= form_radio(array('name'=>'enable_versioning', 'id'=>'enable_versioning_n', 'value'=>'n', 'checked'=>($enable_versioning == 'n') ? TRUE : FALSE)).NBS.lang('no', 'enable_versioning_n');
	$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

	$preference = lang('max_revisions', 'max_revisions');
	$controls = form_input(array('id'=>'max_revisions','name'=>'max_revisions','class'=>'fullfield', 'value'=>$max_revisions));
	$controls .= '<br/>'.form_checkbox(array('name'=>'clear_versioning_data', 'id'=>'clear_versioning_data', 'value'=>'y', 'checked'=>FALSE)).NBS.'<span class="notice">'.lang('clear_versioning_data', 'clear_versioning_data').'</span>';
	$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

	echo $this->table->generate();
	$this->table->clear(); // Clear out for the next one

	// -------------------------------------------------------------
	// Create the Notification Settings table
	$this->table->set_caption(lang('notification_settings'));
	$this->table->set_heading(lang('preference'), lang('setting'));

	$preference = lang('channel_notify', 'channel_notify');
	$controls = form_radio(array('name'=>'channel_notify', 'id'=>'channel_notify_y', 'value'=>'y', 'checked'=>($channel_notify == 'y') ? TRUE : FALSE)).NBS.lang('yes', 'channel_notify_y').NBS.NBS.NBS.NBS.NBS;
	$controls .= form_radio(array('name'=>'channel_notify', 'id'=>'channel_notify_n', 'value'=>'n', 'checked'=>($channel_notify == 'n') ? TRUE : FALSE)).NBS.lang('no', 'channel_notify_n');
	$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

	$preference = lang('comment_notify_emails', 'channel_notify_emails');
	$controls = form_input(array('id'=>'channel_notify_emails','name'=>'channel_notify_emails','class'=>'fullfield', 'value'=>$channel_notify_emails));
	$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

	if (isset($this->cp->installed_modules['comment']))
	{
		$preference = lang('comment_notify', 'comment_notify');
		$controls = form_radio(array('name'=>'comment_notify', 'id'=>'comment_notify_y', 'value'=>'y', 'checked'=>($comment_notify == 'y') ? TRUE : FALSE)).NBS.lang('yes', 'comment_notify_y').NBS.NBS.NBS.NBS.NBS;
		$controls .= form_radio(array('name'=>'comment_notify', 'id'=>'comment_notify_n', 'value'=>'n', 'checked'=>($comment_notify == 'n') ? TRUE : FALSE)).NBS.lang('no', 'comment_notify_n');
		$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

		$preference = lang('comment_notify_emails', 'comment_notify_emails');
		$controls = form_input(array('id'=>'comment_notify_emails','name'=>'comment_notify_emails','class'=>'fullfield', 'value'=>$comment_notify_emails));
		$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

		$preference = lang('comment_notify_authors', 'comment_notify_authors');
		$controls = form_radio(array('name'=>'comment_notify_authors', 'id'=>'comment_notify_authors_y', 'value'=>'y', 'checked'=>($comment_notify_authors == 'y') ? TRUE : FALSE)).NBS.lang('yes', 'comment_notify_authors_y').NBS.NBS.NBS.NBS.NBS;
		$controls .= form_radio(array('name'=>'comment_notify_authors', 'id'=>'comment_notify_authors_n', 'value'=>'n', 'checked'=>($comment_notify_authors == 'n') ? TRUE : FALSE)).NBS.lang('no', 'comment_notify_authors_n');
		$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));
	}

	echo $this->table->generate();
	$this->table->clear(); // Clear out for the next one

	// -------------------------------------------------------------
	// Create the comment preferences table
	if (isset($this->cp->installed_modules['comment']))
	{
		$this->table->set_caption(lang('comment_prefs'));
		$this->table->set_heading(lang('preference'), lang('setting'));

		$preference = lang('comment_system_enabled', 'comment_system_enabled');
		$controls = form_radio(array('name'=>'comment_system_enabled', 'id'=>'comment_system_enabled_y', 'value'=>'y', 'checked'=>($comment_system_enabled == 'y') ? TRUE : FALSE)).NBS.lang('yes', 'comment_system_enabled_y').NBS.NBS.NBS.NBS.NBS;
		$controls .= form_radio(array('name'=>'comment_system_enabled', 'id'=>'comment_system_enabled_n', 'value'=>'n', 'checked'=>($comment_system_enabled == 'n') ? TRUE : FALSE)).NBS.lang('no', 'comment_system_enabled_n');
		$controls .= '<br/>'.form_checkbox(array('name'=>'apply_comment_enabled_to_existing', 'id'=>'apply_comment_enabled_to_existing', 'value'=>'y', 'checked'=>FALSE)).NBS.'<span class="notice">'.lang('update_existing_comments_mod', 'apply_comment_enabled_to_existing').'</span>';
		$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

		$preference = lang('comment_require_membership', 'comment_require_membership');
		$controls = form_radio(array('name'=>'comment_require_membership', 'id'=>'comment_require_membership_y', 'value'=>'y', 'checked'=>($comment_require_membership == 'y') ? TRUE : FALSE)).NBS.lang('yes', 'comment_require_membership_y').NBS.NBS.NBS.NBS.NBS;
		$controls .= form_radio(array('name'=>'comment_require_membership', 'id'=>'comment_require_membership_n', 'value'=>'n', 'checked'=>($comment_require_membership == 'n') ? TRUE : FALSE)).NBS.lang('no', 'comment_require_membership_n');
		$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

		$preference = lang('comment_use_captcha', 'comment_use_captcha').'<br />'.lang('captcha_explanation');
		$controls = form_radio(array('name'=>'comment_use_captcha', 'id'=>'comment_use_captcha_y', 'value'=>'y', 'checked'=>($comment_use_captcha == 'y') ? TRUE : FALSE)).NBS.lang('yes', 'comment_use_captcha_y').NBS.NBS.NBS.NBS.NBS;
		$controls .= form_radio(array('name'=>'comment_use_captcha', 'id'=>'comment_use_captcha_n', 'value'=>'n', 'checked'=>($comment_use_captcha == 'n') ? TRUE : FALSE)).NBS.lang('no', 'comment_use_captcha_n');
		$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

		$preference = lang('comment_require_email', 'comment_require_email');
		$controls = form_radio(array('name'=>'comment_require_email', 'id'=>'comment_require_email_y', 'value'=>'y', 'checked'=>($comment_require_email == 'y') ? TRUE : FALSE)).NBS.lang('yes', 'comment_require_email_y').NBS.NBS.NBS.NBS.NBS;
		$controls .= form_radio(array('name'=>'comment_require_email', 'id'=>'comment_require_email_n', 'value'=>'n', 'checked'=>($comment_require_email == 'n') ? TRUE : FALSE)).NBS.lang('no', 'comment_require_email_n');
		$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

		$preference = lang('comment_moderate', 'comment_moderate').'<br />'.lang('comment_moderate_exp');
		$controls = form_radio(array('name'=>'comment_moderate', 'id'=>'comment_moderate_y', 'value'=>'y', 'checked'=>($comment_moderate == 'y') ? TRUE : FALSE)).NBS.lang('yes', 'comment_moderate_y').NBS.NBS.NBS.NBS.NBS;
		$controls .= form_radio(array('name'=>'comment_moderate', 'id'=>'comment_moderate_n', 'value'=>'n', 'checked'=>($comment_moderate == 'n') ? TRUE : FALSE)).NBS.lang('no', 'comment_moderate_n');
		$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

		$preference = lang('comment_max_chars', 'comment_max_chars');
		$controls = form_input(array('id'=>'comment_max_chars','name'=>'comment_max_chars','class'=>'fullfield', 'value'=>$comment_max_chars));
		$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

		$preference = lang('comment_timelock', 'comment_timelock').'<br />'.lang('comment_timelock_desc');
		$controls = form_input(array('id'=>'comment_timelock','name'=>'comment_timelock','class'=>'fullfield', 'value'=>$comment_timelock));
		$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

		$preference = lang('comment_expiration', 'comment_expiration').'<br />'.lang('comment_expiration_desc');
		$controls = form_input(array('id'=>'comment_expiration','name'=>'comment_expiration','class'=>'fullfield', 'value'=>set_value('comment_expiration', $comment_expiration)));
		$controls .= '<br/>'.form_checkbox(array('name'=>'apply_expiration_to_existing', 'id'=>'apply_expiration_to_existing', 'value'=>'y', 'checked'=>FALSE)).NBS.'<span class="notice">'.lang('update_existing_comments', 'apply_expiration_to_existing').'</span>';
		$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

		$preference = lang('comment_text_formatting', 'comment_text_formatting');
		$controls = form_dropdown('comment_text_formatting', $comment_text_formatting_options, $comment_text_formatting, 'id="comment_text_formatting"');
		$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

		$preference = lang('comment_html_formatting', 'comment_html_formatting');
		$controls = form_dropdown('comment_html_formatting', $comment_html_formatting_options, $comment_html_formatting, 'id="comment_html_formatting"');
		$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

		$preference = lang('comment_allow_img_urls', 'comment_allow_img_urls');
		$controls = form_radio(array('name'=>'comment_allow_img_urls', 'id'=>'comment_allow_img_urls_y', 'value'=>'y', 'checked'=>($comment_allow_img_urls == 'y') ? TRUE : FALSE)).NBS.lang('yes', 'comment_allow_img_urls_y').NBS.NBS.NBS.NBS.NBS;
		$controls .= form_radio(array('name'=>'comment_allow_img_urls', 'id'=>'comment_allow_img_urls_n', 'value'=>'n', 'checked'=>($comment_allow_img_urls == 'n') ? TRUE : FALSE)).NBS.lang('no', 'comment_allow_img_urls_n');
		$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

		$preference = lang('auto_link_urls', 'comment_auto_link_urls');
		$controls = form_radio(array('name'=>'comment_auto_link_urls', 'id'=>'comment_auto_link_urls_y', 'value'=>'y', 'checked'=>($comment_auto_link_urls == 'y') ? TRUE : FALSE)).NBS.lang('yes', 'comment_auto_link_urls_y').NBS.NBS.NBS.NBS.NBS;
		$controls .= form_radio(array('name'=>'comment_auto_link_urls', 'id'=>'comment_auto_link_urls_n', 'value'=>'n', 'checked'=>($comment_auto_link_urls == 'n') ? TRUE : FALSE)).NBS.lang('no', 'comment_auto_link_urls_n');
		$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

		echo $this->table->generate();
		$this->table->clear(); // Clear out for the next one
	}

	// -------------------------------------------------------------
	// Create the Publish Page Customization table
	$this->table->set_caption(lang('publish_page_customization'));
	$this->table->set_heading(lang('preference'), lang('setting'));

	$preference = lang('show_button_cluster', 'show_button_cluster');
	$controls = form_radio(array('name'=>'show_button_cluster', 'id'=>'show_button_cluster_y', 'value'=>'y', 'checked'=>($show_button_cluster == 'y') ? TRUE : FALSE)).NBS.lang('yes', 'show_button_cluster_y').NBS.NBS.NBS.NBS.NBS;
	$controls .= form_radio(array('name'=>'show_button_cluster', 'id'=>'show_button_cluster_n', 'value'=>'n', 'checked'=>($show_button_cluster == 'n') ? TRUE : FALSE)).NBS.lang('no', 'show_button_cluster_n');
	$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

	$preference = lang('default_entry_title', 'default_entry_title');
	$controls = form_input(array('id'=>'default_entry_title','name'=>'default_entry_title','class'=>'fullfield', 'value'=>$default_entry_title));
	$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

	$preference = lang('url_title_prefix', 'url_title_prefix').'<br />'.lang('single_word_no_spaces').form_error('url_title_prefix');
	$controls = form_input(array('id'=>'url_title_prefix','name'=>'url_title_prefix','class'=>'fullfield', 'value'=>set_value('url_title_prefix', $url_title_prefix)));
	$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

	echo $this->table->generate();
	$this->table->clear(); // Clear out for the next one
?>

<p style="margin-top: 15px;">
	<?=form_submit(array('name' => 'channel_prefs_submit', 'value' => lang('update'), 'class' => 'submit'))?>
	<?=form_submit(array('name' => 'return', 'value' => lang('update_and_return'), 'class' => 'submit'))?>
</p>
<?=form_close()?>
