<?php $page_title = 'Add-Ons';
include(dirname(__FILE__) . '/_wrapper-head.php'); ?>


<div class="tab-bar">
	<div class="tab-bar__tabs">
		<a href="" class="tab-bar__tab active">Installed</a>
		<!-- <a href="" class="tab-bar__tab">Uninstalled</a> -->
		<!-- <a href="" class="tab-bar__tab">Add-On Store</a> -->
		<a href="" class="tab-bar__tab">Updates <span class="tab-bar__tab-notification">2</span></a>
	</div>
</div>

<div class="add-on-card-list">
	<div class="add-on-card add-on-card--clickable">
			<div class="add-on-card__icon">
				<div class="add-on-card__image">
					<img src="../app/assets/images/default-addon-on-icon.png" alt="default app icon" class="">
				</div>
				<!-- <span class="price">2.0.0</span> -->
			</div>
			<div class="add-on-card__text">

				<h2 class="add-on-card__title">Structure <span class="add-on-card__title-version">2.0.0</span></h2>
				<p class="add-on-card__desc">Create pages, generate navigation, manage content through a simple interface and build robust sites faster than ever.</p>
			</div>
			<span class="add-on-card__cog"><i class="fal fa-cog"></i></span>
	</div>
	<div class="add-on-card">
			<div class="add-on-card__icon">
				<div class="add-on-card__image">
					<img src="../app/assets/images/default-addon-on-icon.png" alt="default app icon" class="">
				</div>
			</div>
			<div class="add-on-card__text">
				<h2 class="add-on-card__title">Super Restricted Entries <span class="add-on-card__title-version">2.0.0</span></h2>
				<p class="add-on-card__desc">Super restricted entries is created to allow admin to restrict members or</p>
			</div>
			<span class="add-on-card__cog"><i class="fal fa-cog"></i></span>
	</div>
	<div class="add-on-card add-on-card--selected">
			<div class="add-on-card__icon">
				<div class="add-on-card__image">
					<img src="../app/assets/images/default-addon-on-icon.png" alt="default app icon" class="">
				</div>
			</div>
			<div class="add-on-card__text">
				<h2 class="add-on-card__title">Rocket <span class="add-on-card__title-version">2.0.0</span></h2>
				<p class="add-on-card__desc">Dramatically improve the loading speed of your pages, and reduce</p>
			</div>
			<span class="add-on-card__cog"><i class="fal fa-cog"></i></span>
	</div>
</div>

<h4 class="line-heading">Disabled</h4>
<hr>

<div class="add-on-card-list">
	<div class="add-on-card add-on-card--uninstalled">
		<div class="add-on-card__icon">
			<div class="add-on-card__image">
				<img src="../app/assets/images/default-addon-on-icon.png" alt="default app icon" class="">
			</div>
			<!-- <span class="price">5.0.1</span> -->
		</div>
		<div class="add-on-card__text">
			<h2 class="add-on-card__title">Freeform <span class="add-on-card__title-version">2.0.0</span></h2>
			<p class="add-on-card__desc">The most reliable, intuitive and powerful form builder for</p>
		</div>
		<div class="add-on-card__button">
			<a href="" class="button button--primary">Install</a>
		</div>
	</div>
</div>



<?php include(dirname(__FILE__) . '/_wrapper-footer.php'); ?>
<section class="ee-debugger">
	<div class="ee-debugger__inner">
		<h1 class="ee-debugger__title">/cp/msm</h1>
		<div class="tab-wrap">
			<ul class="tabs">
								<li><a class="act" href="" rel="t-0"><b>0.0975s</b> Load</a></li>
								<li><a href="" rel="t-1">Variables</a></li>
								<li><a href="" rel="t-2">32 Queries</a></li>
						</ul>
			<div class="tab t-0 tab-open">
		<div class="debug-content">
			<h2>Performance</h2>
						<ul class="arrow-list">
										<li><b>Memory Usage:</b> 14.9<abbr title="Megabytes">MB</abbr> of 128M</li>
										<li><b>Database Execution Time:</b> 0.0118</li>
										<li><b>Total Execution Time:</b> 0.0975</li>
								</ul>
				</div>
	</div>
	<div class="tab t-1 tab-open">
		<div class="debug-content">
							<h2>$_SERVER</h2>
								<ul class="var-list">
												<li><code>HTTP_ACCEPT:</code> text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8</li>
												<li><code>HTTP_USER_AGENT:</code> Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:70.0) Gecko/20100101 Firefox/70.0</li>
												<li><code>HTTP_CONNECTION:</code> keep-alive</li>
												<li><code>SERVER_PORT:</code> 80</li>
												<li><code>SERVER_NAME:</code> alpha1.local</li>
												<li><code>REMOTE_ADDR:</code> ::1</li>
												<li><code>SERVER_SOFTWARE:</code> Apache/2.4.41 (Unix) PHP/7.3.8 LibreSSL/2.8.3</li>
												<li><code>HTTP_ACCEPT_LANGUAGE:</code> en-US,en;q=0.5</li>
												<li><code>SCRIPT_NAME:</code> /new-cp/admin.php</li>
												<li><code>REQUEST_METHOD:</code> GET</li>
												<li><code>HTTP_HOST:</code> alpha1.local</li>
												<li><code>REMOTE_HOST:</code> </li>
												<li><code>CONTENT_TYPE:</code> </li>
												<li><code>SERVER_PROTOCOL:</code> HTTP/1.1</li>
												<li><code>QUERY_STRING:</code> /cp/msm</li>
												<li><code>HTTP_ACCEPT_ENCODING:</code> gzip, deflate</li>
												<li><code>HTTP_X_FORWARDED_FOR:</code> </li>
										</ul>
									<h2>$_COOKIE</h2>
								<ul class="var-list">
												<li><code>exp_last_visit:</code> 1570485848</li>
												<li><code>exp_last_activity:</code> 1570658633</li>
												<li><code>1031b8c41dfff97a311a7ac99863bdc5_username:</code> 030e6a267a572de46ecae3fbaf314d561355edebb94f58cf382b87b9552875e5a:2:{i:0;s:41:"1031b8c41dfff97a311a7ac99863bdc5_username";i:1;s:6:"jordan";}</li>
												<li><code>CraftSessionId:</code> in812tkj2it5j5io8o51ksa0l5</li>
												<li><code>CRAFT_CSRF_TOKEN:</code> 604f6b25969b66b2c4a572c29d16915a1ffbe1e94134bbe9499f2ad8d4003136a:2:{i:0;s:16:"CRAFT_CSRF_TOKEN";i:1;s:208:"BQY04THoitYN5RSYsMP3GDdpOIjFdzqaT5sERHaV|fe0dd48948a2935ecabe84fcd283a39dd1c21037428bfd833cebd3137e3811a4BQY04THoitYN5RSYsMP3GDdpOIjFdzqaT5sERHaV|1|$2y$13$.LsEYcJeRftQL12UhjJCCuwUPQ4RPWAUy65Wjk3Wju0JElnnO7lfS";}</li>
												<li><code>exp_tracker:</code> {"0":"index","token":"fa00ef00ba0e8663a36ea7cd485bf7ab3849bbe7ec4f236c6d15f1ddff03ec0e01be45f0a2fb02943af2ca4b462b7d5e"}</li>
												<li><code>exp_sessionid:</code> 11ce7078cae173aa2c784b0fdd1918ead4307927</li>
												<li><code>exp_remember:</code> 962b97ef3e91dff332858860b17751a2fd25a29b</li>
												<li><code>exp_flash:</code> {":old:alert:inline:shared-form":{"title":"Status Created","body":"&lt;p&gt;The status &lt;b&gt;Green&lt;/b&gt; has been created.&lt;/p&gt;","severity":"success","can_close":true}}228ee49ba978da3dbbd90f04bade5c6f645345956001d45626ed2022dfe235731958ea771eb67ec8fe891033b2133db1</li>
												<li><code>exp_cp_last_site_id:</code> 2</li>
										</ul>
									<h2>$_GET</h2>
								<ul class="var-list">
												<li><code>D:</code> cp</li>
												<li><code>C:</code> msm</li>
										</ul>
									<h2>$_POST</h2>
								<div class="no-results">No <code><b>$_POST</b></code> variables found.</div>
									<h2>Userdata</h2>
								<ul class="var-list">
												<li><code>username:</code> jordan</li>
												<li><code>screen_name:</code> Jordan Ellis</li>
												<li><code>email:</code> j@j.com</li>
												<li><code>url:</code> </li>
												<li><code>location:</code> </li>
												<li><code>language:</code> english</li>
												<li><code>timezone:</code> </li>
												<li><code>date_format:</code> %n/%j/%Y</li>
												<li><code>time_format:</code> 12</li>
												<li><code>include_seconds:</code> n</li>
												<li><code>group_id:</code> 1</li>
												<li><code>access_cp:</code> 0</li>
												<li><code>last_visit:</code> 1570485848</li>
												<li><code>is_banned:</code> </li>
												<li><code>ignore_list:</code> Array
	(
	)
	</li>
												<li><code>member_id:</code> 1</li>
												<li><code>authcode:</code> </li>
												<li><code>signature:</code> </li>
												<li><code>avatar_filename:</code> avatar_1.jpg</li>
												<li><code>avatar_width:</code> </li>
												<li><code>avatar_height:</code> </li>
												<li><code>photo_filename:</code> </li>
												<li><code>photo_width:</code> </li>
												<li><code>photo_height:</code> </li>
												<li><code>sig_img_filename:</code> </li>
												<li><code>sig_img_width:</code> </li>
												<li><code>sig_img_height:</code> </li>
												<li><code>private_messages:</code> 0</li>
												<li><code>accept_messages:</code> y</li>
												<li><code>last_view_bulletins:</code> 0</li>
												<li><code>last_bulletin_date:</code> 0</li>
												<li><code>ip_address:</code> ::1</li>
												<li><code>join_date:</code> 1565191303</li>
												<li><code>last_activity:</code> 1570658380</li>
												<li><code>total_entries:</code> 7</li>
												<li><code>total_comments:</code> 0</li>
												<li><code>total_forum_topics:</code> 0</li>
												<li><code>total_forum_posts:</code> 0</li>
												<li><code>last_entry_date:</code> 1568065320</li>
												<li><code>last_comment_date:</code> 0</li>
												<li><code>last_forum_post_date:</code> 0</li>
												<li><code>last_email_date:</code> 0</li>
												<li><code>in_authorlist:</code> n</li>
												<li><code>accept_admin_email:</code> y</li>
												<li><code>accept_user_email:</code> y</li>
												<li><code>notify_by_default:</code> y</li>
												<li><code>notify_of_pm:</code> y</li>
												<li><code>display_avatars:</code> n</li>
												<li><code>display_signatures:</code> y</li>
												<li><code>parse_smileys:</code> y</li>
												<li><code>smart_notifications:</code> y</li>
												<li><code>profile_theme:</code> </li>
												<li><code>forum_theme:</code> </li>
												<li><code>tracker:</code> </li>
												<li><code>template_size:</code> 28</li>
												<li><code>notepad:</code> </li>
												<li><code>notepad_size:</code> 18</li>
												<li><code>bookmarklets:</code> {"1":{"name":"Test","channel":"1","field":"2"}}</li>
												<li><code>quick_links:</code> </li>
												<li><code>quick_tabs:</code> </li>
												<li><code>show_sidebar:</code> n</li>
												<li><code>pmember_id:</code> 0</li>
												<li><code>rte_enabled:</code> y</li>
												<li><code>rte_toolset_id:</code> 0</li>
												<li><code>cp_homepage:</code> </li>
												<li><code>cp_homepage_channel:</code> 0</li>
												<li><code>cp_homepage_custom:</code> </li>
												<li><code>site_id:</code> 2</li>
												<li><code>menu_set_id:</code> 1</li>
												<li><code>group_title:</code> Super Admin</li>
												<li><code>group_description:</code> </li>
												<li><code>is_locked:</code> y</li>
												<li><code>can_view_offline_system:</code> y</li>
												<li><code>can_view_online_system:</code> y</li>
												<li><code>can_access_cp:</code> y</li>
												<li><code>can_access_footer_report_bug:</code> y</li>
												<li><code>can_access_footer_new_ticket:</code> y</li>
												<li><code>can_access_footer_user_guide:</code> y</li>
												<li><code>can_view_homepage_news:</code> y</li>
												<li><code>can_access_files:</code> y</li>
												<li><code>can_access_design:</code> y</li>
												<li><code>can_access_addons:</code> y</li>
												<li><code>can_access_members:</code> y</li>
												<li><code>can_access_sys_prefs:</code> y</li>
												<li><code>can_access_comm:</code> y</li>
												<li><code>can_access_utilities:</code> y</li>
												<li><code>can_access_data:</code> y</li>
												<li><code>can_access_logs:</code> y</li>
												<li><code>can_admin_channels:</code> y</li>
												<li><code>can_admin_design:</code> y</li>
												<li><code>can_delete_members:</code> y</li>
												<li><code>can_admin_mbr_groups:</code> y</li>
												<li><code>can_admin_mbr_templates:</code> y</li>
												<li><code>can_ban_users:</code> y</li>
												<li><code>can_admin_addons:</code> y</li>
												<li><code>can_edit_categories:</code> y</li>
												<li><code>can_delete_categories:</code> y</li>
												<li><code>can_view_other_entries:</code> y</li>
												<li><code>can_edit_other_entries:</code> y</li>
												<li><code>can_assign_post_authors:</code> y</li>
												<li><code>can_delete_self_entries:</code> y</li>
												<li><code>can_delete_all_entries:</code> y</li>
												<li><code>can_view_other_comments:</code> y</li>
												<li><code>can_edit_own_comments:</code> y</li>
												<li><code>can_delete_own_comments:</code> y</li>
												<li><code>can_edit_all_comments:</code> y</li>
												<li><code>can_delete_all_comments:</code> y</li>
												<li><code>can_moderate_comments:</code> y</li>
												<li><code>can_send_cached_email:</code> y</li>
												<li><code>can_email_member_groups:</code> y</li>
												<li><code>can_email_from_profile:</code> y</li>
												<li><code>can_view_profiles:</code> y</li>
												<li><code>can_edit_html_buttons:</code> y</li>
												<li><code>can_delete_self:</code> y</li>
												<li><code>mbr_delete_notify_emails:</code> </li>
												<li><code>can_post_comments:</code> y</li>
												<li><code>exclude_from_moderation:</code> y</li>
												<li><code>can_search:</code> y</li>
												<li><code>search_flood_control:</code> 0</li>
												<li><code>can_send_private_messages:</code> y</li>
												<li><code>prv_msg_send_limit:</code> 20</li>
												<li><code>prv_msg_storage_limit:</code> 60</li>
												<li><code>can_attach_in_private_messages:</code> y</li>
												<li><code>can_send_bulletins:</code> y</li>
												<li><code>include_in_authorlist:</code> y</li>
												<li><code>include_in_memberlist:</code> y</li>
												<li><code>can_create_entries:</code> y</li>
												<li><code>can_edit_self_entries:</code> y</li>
												<li><code>can_upload_new_files:</code> y</li>
												<li><code>can_edit_files:</code> y</li>
												<li><code>can_delete_files:</code> y</li>
												<li><code>can_upload_new_toolsets:</code> y</li>
												<li><code>can_edit_toolsets:</code> y</li>
												<li><code>can_delete_toolsets:</code> y</li>
												<li><code>can_create_upload_directories:</code> y</li>
												<li><code>can_edit_upload_directories:</code> y</li>
												<li><code>can_delete_upload_directories:</code> y</li>
												<li><code>can_create_channels:</code> y</li>
												<li><code>can_edit_channels:</code> y</li>
												<li><code>can_delete_channels:</code> y</li>
												<li><code>can_create_channel_fields:</code> y</li>
												<li><code>can_edit_channel_fields:</code> y</li>
												<li><code>can_delete_channel_fields:</code> y</li>
												<li><code>can_create_statuses:</code> y</li>
												<li><code>can_delete_statuses:</code> y</li>
												<li><code>can_edit_statuses:</code> y</li>
												<li><code>can_create_categories:</code> y</li>
												<li><code>can_create_member_groups:</code> y</li>
												<li><code>can_delete_member_groups:</code> y</li>
												<li><code>can_edit_member_groups:</code> y</li>
												<li><code>can_create_members:</code> y</li>
												<li><code>can_edit_members:</code> y</li>
												<li><code>can_create_new_templates:</code> y</li>
												<li><code>can_edit_templates:</code> y</li>
												<li><code>can_delete_templates:</code> y</li>
												<li><code>can_create_template_groups:</code> y</li>
												<li><code>can_edit_template_groups:</code> y</li>
												<li><code>can_delete_template_groups:</code> y</li>
												<li><code>can_create_template_partials:</code> y</li>
												<li><code>can_edit_template_partials:</code> y</li>
												<li><code>can_delete_template_partials:</code> y</li>
												<li><code>can_create_template_variables:</code> y</li>
												<li><code>can_delete_template_variables:</code> y</li>
												<li><code>can_edit_template_variables:</code> y</li>
												<li><code>can_access_security_settings:</code> y</li>
												<li><code>can_access_translate:</code> y</li>
												<li><code>can_access_import:</code> y</li>
												<li><code>can_access_sql_manager:</code> y</li>
												<li><code>can_moderate_spam:</code> n</li>
												<li><code>can_manage_consents:</code> y</li>
												<li><code>total_forum_replies:</code> 0</li>
												<li><code>display_photos:</code> n</li>
												<li><code>assigned_channels:</code> Array
	(
	)
	</li>
												<li><code>assigned_modules:</code> Array
	(
	)
	</li>
												<li><code>assigned_template_groups:</code> Array
	(
	)
	</li>
												<li><code>assigned_sites:</code> Array
	(
	    [2] =&gt; Jim &amp; Bones
	    [1] =&gt; Kirk's Phasers
	)
	</li>
												<li><code>admin_sess:</code> 1</li>
												<li><code>user_agent:</code> Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:70.0) Gecko/20100101 Firefox/70.0</li>
												<li><code>sess_start:</code> 1570658554</li>
												<li><code>can_debug:</code> </li>
										</ul>
								</div>
	</div><div class="tab t-2 tab-open">
		<div class="debug-content">
						<h2>Duplicate Queries (new-cp)</h2>
								<div class="no-results">No duplicate queries.</div>

						<h2>Queries (new-cp)</h2>
				<ul class="query-list">
															<li>
							<div class="query-time">
								0.0001s
								<i>656<abbr title="Bytes">B</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'</code></pre></div>
							<div class="query-file">
	#system/ee/EllisLab/ExpressionEngine/Service/Addon/Addon.php L:57  Addons_model::get_installed_modules() </div>
						</li>
															<li>
							<div class="query-time">
								0.0003s
								<i>40<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SELECT LOWER(module_name) AS module_name, module_version, has_cp_backend, module_id
	FROM (`exp_modules`)</code></pre></div>
							<div class="query-file">
	#system/ee/EllisLab/ExpressionEngine/Service/Addon/Addon.php L:57  Addons_model::get_installed_modules() </div>
						</li>
															<li>
							<div class="query-time">
								0.0002s
								<i>38<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SELECT `class`, `version`
	FROM (`exp_extensions`)
	WHERE `enabled` =  'y'</code></pre></div>
							<div class="query-file">
	#system/ee/EllisLab/ExpressionEngine/Service/Addon/Addon.php L:74  Addons_model::get_installed_extensions() </div>
						</li>
															<li>
							<div class="query-time">
								0.0001s
								<i>38<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SELECT `name`
	FROM (`exp_fieldtypes`)</code></pre></div>
							<div class="query-file">
	#system/ee/EllisLab/ExpressionEngine/Service/Addon/Factory.php L:85  EllisLab\ExpressionEngine\Service\Addon\Addon::isInstalled() </div>
						</li>
															<li>
							<div class="query-time">
								0.0001s
								<i>38<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SELECT `plugin_package`
	FROM (`exp_plugins`)</code></pre></div>
							<div class="query-file">
	#system/ee/EllisLab/ExpressionEngine/Service/Addon/Factory.php L:85  EllisLab\ExpressionEngine\Service\Addon\Addon::isInstalled() </div>
						</li>
															<li>
							<div class="query-time">
								0.0016s
								<i>28<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SHOW TABLES FROM `new-cp`</code></pre></div>
							<div class="query-file">
	#system/ee/legacy/libraries/Core.php L:120  EE_Config::site_prefs() </div>
						</li>
															<li>
							<div class="query-time">
								0.0003s
								<i>39<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SELECT Config_config.config_id as Config__config_id, Config_config.site_id as Config__site_id, Config_config.key as Config__key, Config_config.value as Config__value
	FROM (`exp_config` as Config_config)
	WHERE  (
	`Config_config`.`site_id`  =  0
	AND `Config_config`.`key`  =  'multiple_sites_enabled'
	)
	LIMIT 1</code></pre></div>
							<div class="query-file">
	#system/ee/legacy/libraries/Core.php L:120  EE_Config::site_prefs() </div>
						</li>
															<li>
							<div class="query-time">
								0.0003s
								<i>38<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SELECT *
	FROM (`exp_sites`)
	WHERE `site_id` =  1</code></pre></div>
							<div class="query-file">
	#system/ee/legacy/libraries/Core.php L:120  EE_Config::site_prefs() </div>
						</li>
															<li>
							<div class="query-time">
								0.0004s
								<i>53<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SELECT Config_config.config_id as Config__config_id, Config_config.site_id as Config__site_id, Config_config.key as Config__key, Config_config.value as Config__value
	FROM (`exp_config` as Config_config)
	WHERE  (
	`Config_config`.`site_id`  IN (0, 1)
	)
	LIMIT 18446744073709551615</code></pre></div>
							<div class="query-file">
	#system/ee/legacy/libraries/Core.php L:120  EE_Config::site_prefs() </div>
						</li>
															<li>
							<div class="query-time">
								0.0002s
								<i>38<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SELECT *
	FROM (`exp_sites`)
	WHERE `site_id` =  '2'</code></pre></div>
							<div class="query-file">
	#system/ee/legacy/libraries/Core.php L:155  EE_Config::site_prefs() </div>
						</li>
															<li>
							<div class="query-time">
								0.0004s
								<i>53<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SELECT Config_config.config_id as Config__config_id, Config_config.site_id as Config__site_id, Config_config.key as Config__key, Config_config.value as Config__value
	FROM (`exp_config` as Config_config)
	WHERE  (
	`Config_config`.`site_id`  IN (0, 2)
	)
	LIMIT 18446744073709551615</code></pre></div>
							<div class="query-file">
	#system/ee/legacy/libraries/Core.php L:155  EE_Config::site_prefs() </div>
						</li>
															<li>
							<div class="query-time">
								0.0004s
								<i>39<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SELECT DISTINCT ee.* FROM exp_extensions ee WHERE enabled = 'y' ORDER BY hook, priority ASC, class</code></pre></div>
							<div class="query-file">
	#system/ee/legacy/core/Loader.php L:1039  EE_Extensions::__construct() </div>
						</li>
															<li>
							<div class="query-time">
								0.0002s
								<i>39<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SELECT *
	FROM (`exp_remember_me`)
	WHERE `remember_me_id` =  '962b97ef3e91dff332858860b17751a2fd25a29b'</code></pre></div>
							<div class="query-file">
	#system/ee/legacy/libraries/Remember.php L:100  Remember::_validate_db() </div>
						</li>
															<li>
							<div class="query-time">
								0.0003s
								<i>41<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SELECT Session_sessions.session_id as Session__session_id, Session_sessions.member_id as Session__member_id, Session_sessions.admin_sess as Session__admin_sess, Session_sessions.ip_address as Session__ip_address, Session_sessions.user_agent as Session__user_agent, Session_sessions.login_state as Session__login_state, Session_sessions.fingerprint as Session__fingerprint, Session_sessions.sess_start as Session__sess_start, Session_sessions.auth_timeout as Session__auth_timeout, Session_sessions.last_activity as Session__last_activity, Session_sessions.can_debug as Session__can_debug
	FROM (`exp_sessions` as Session_sessions)
	WHERE  (
	`Session_sessions`.`session_id`  =  '11ce7078cae173aa2c784b0fdd1918ead4307927'
	)
	LIMIT 1</code></pre></div>
							<div class="query-file">
	#system/ee/legacy/libraries/Session.php L:164  EE_Session::fetch_session_data() </div>
						</li>
															<li>
							<div class="query-time">
								0.0006s
								<i>237<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SELECT *
	FROM (`exp_members` m, `exp_member_groups` g)
	WHERE `g`.`site_id` =  2
	AND m.group_id =  g.group_id
	AND `member_id` =  1</code></pre></div>
							<div class="query-file">
	#system/ee/legacy/libraries/Session.php L:566  EE_Session::_do_member_query() </div>
						</li>
															<li>
							<div class="query-time">
								0.0002s
								<i>38<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SELECT `channel_id`, `channel_title`
	FROM (`exp_channels`)
	WHERE `site_id` =  2
	ORDER BY `channel_title`</code></pre></div>
							<div class="query-file">
	#system/ee/legacy/libraries/Session.php L:630  EE_Session::_setup_channel_privs() </div>
						</li>
															<li>
							<div class="query-time">
								0.0002s
								<i>38<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SELECT `module_id`
	FROM (`exp_module_member_groups`)
	WHERE `group_id` =  1</code></pre></div>
							<div class="query-file">
	#system/ee/legacy/libraries/Session.php L:631  EE_Session::_setup_module_privs() </div>
						</li>
															<li>
							<div class="query-time">
								0.0001s
								<i>38<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SELECT `template_group_id`
	FROM (`exp_template_member_groups`)
	WHERE `group_id` =  1</code></pre></div>
							<div class="query-file">
	#system/ee/legacy/libraries/Session.php L:632  EE_Session::_setup_template_privs() </div>
						</li>
															<li>
							<div class="query-time">
								0.0001s
								<i>38<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SELECT `site_id`, `site_label`
	FROM (`exp_sites`)
	ORDER BY `site_label`</code></pre></div>
							<div class="query-file">
	#system/ee/legacy/libraries/Session.php L:633  EE_Session::_setup_assigned_sites() </div>
						</li>
															<li>
							<div class="query-time">
								0.0008s
								<i>1<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">UPDATE `exp_sessions` SET `session_id` = '11ce7078cae173aa2c784b0fdd1918ead4307927', `fingerprint` = '40a8b27227eed18763adc62870639ec4', `member_id` = 1, `admin_sess` = 1, `ip_address` = '::1', `user_agent` = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:70.0) Gecko/20100101 Firefox/70.0', `last_activity` = 1570658636, `sess_start` = 1570658554, `can_debug` = 0 WHERE session_id = '11ce7078cae173aa2c784b0fdd1918ead4307927'</code></pre></div>
							<div class="query-file">
	#system/ee/legacy/libraries/Session.php L:180  EE_Session::update_session() </div>
						</li>
															<li>
							<div class="query-time">
								0.0003s
								<i>38<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SELECT *
	FROM (`exp_security_hashes`)
	WHERE `session_id` =  '11ce7078cae173aa2c784b0fdd1918ead4307927'</code></pre></div>
							<div class="query-file">
	#system/ee/legacy/libraries/Csrf.php L:162  Csrf_database::fetch_token() </div>
						</li>
															<li>
							<div class="query-time">
								0.0008s
								<i>17<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SHOW COLUMNS FROM `exp_member_data`</code></pre></div>
							<div class="query-file">
	#system/ee/EllisLab/ExpressionEngine/Service/Model/MetaDataReader.php L:166  EllisLab\ExpressionEngine\Model\Content\VariableColumnGateway::getFieldList() </div>
						</li>
															<li>
							<div class="query-time">
								0.0005s
								<i>105<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SELECT Member_members.member_id as Member__member_id, Member_members.group_id as Member__group_id, Member_members.username as Member__username, Member_members.screen_name as Member__screen_name, Member_members.password as Member__password, Member_members.salt as Member__salt, Member_members.unique_id as Member__unique_id, Member_members.crypt_key as Member__crypt_key, Member_members.authcode as Member__authcode, Member_members.email as Member__email, Member_members.signature as Member__signature, Member_members.avatar_filename as Member__avatar_filename, Member_members.avatar_width as Member__avatar_width, Member_members.avatar_height as Member__avatar_height, Member_members.photo_filename as Member__photo_filename, Member_members.photo_width as Member__photo_width, Member_members.photo_height as Member__photo_height, Member_members.sig_img_filename as Member__sig_img_filename, Member_members.sig_img_width as Member__sig_img_width, Member_members.sig_img_height as Member__sig_img_height, Member_members.ignore_list as Member__ignore_list, Member_members.private_messages as Member__private_messages, Member_members.accept_messages as Member__accept_messages, Member_members.last_view_bulletins as Member__last_view_bulletins, Member_members.last_bulletin_date as Member__last_bulletin_date, Member_members.ip_address as Member__ip_address, Member_members.join_date as Member__join_date, Member_members.last_visit as Member__last_visit, Member_members.last_activity as Member__last_activity, Member_members.total_entries as Member__total_entries, Member_members.total_comments as Member__total_comments, Member_members.total_forum_topics as Member__total_forum_topics, Member_members.total_forum_posts as Member__total_forum_posts, Member_members.last_entry_date as Member__last_entry_date, Member_members.last_comment_date as Member__last_comment_date, Member_members.last_forum_post_date as Member__last_forum_post_date, Member_members.last_email_date as Member__last_email_date, Member_members.in_authorlist as Member__in_authorlist, Member_members.accept_admin_email as Member__accept_admin_email, Member_members.accept_user_email as Member__accept_user_email, Member_members.notify_by_default as Member__notify_by_default, Member_members.notify_of_pm as Member__notify_of_pm, Member_members.display_avatars as Member__display_avatars, Member_members.display_signatures as Member__display_signatures, Member_members.parse_smileys as Member__parse_smileys, Member_members.smart_notifications as Member__smart_notifications, Member_members.language as Member__language, Member_members.timezone as Member__timezone, Member_members.time_format as Member__time_format, Member_members.date_format as Member__date_format, Member_members.include_seconds as Member__include_seconds, Member_members.profile_theme as Member__profile_theme, Member_members.forum_theme as Member__forum_theme, Member_members.tracker as Member__tracker, Member_members.template_size as Member__template_size, Member_members.notepad as Member__notepad, Member_members.notepad_size as Member__notepad_size, Member_members.bookmarklets as Member__bookmarklets, Member_members.quick_links as Member__quick_links, Member_members.quick_tabs as Member__quick_tabs, Member_members.show_sidebar as Member__show_sidebar, Member_members.pmember_id as Member__pmember_id, Member_members.rte_enabled as Member__rte_enabled, Member_members.rte_toolset_id as Member__rte_toolset_id, Member_members.cp_homepage as Member__cp_homepage, Member_members.cp_homepage_channel as Member__cp_homepage_channel, Member_members.cp_homepage_custom as Member__cp_homepage_custom, Member_member_data.member_id as Member__member_id, Member_member_data.member_id as Member__member_id
	FROM (`exp_members` as Member_members, `exp_member_data` as Member_member_data)
	WHERE Member_member_data.member_id = Member_members.member_id
	AND (
	`Member_member_data`.`member_id`  =  1
	)
	LIMIT 1</code></pre></div>
							<div class="query-file">
	#system/ee/legacy/libraries/Core.php L:475  Cp::set_default_view_variables() </div>
						</li>
															<li>
							<div class="query-time">
								0.0003s
								<i>45<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SELECT MemberField_member_fields.m_field_id as MemberField__m_field_id, MemberField_member_fields.m_field_name as MemberField__m_field_name, MemberField_member_fields.m_field_label as MemberField__m_field_label, MemberField_member_fields.m_field_description as MemberField__m_field_description, MemberField_member_fields.m_field_type as MemberField__m_field_type, MemberField_member_fields.m_field_list_items as MemberField__m_field_list_items, MemberField_member_fields.m_field_ta_rows as MemberField__m_field_ta_rows, MemberField_member_fields.m_field_maxl as MemberField__m_field_maxl, MemberField_member_fields.m_field_width as MemberField__m_field_width, MemberField_member_fields.m_field_search as MemberField__m_field_search, MemberField_member_fields.m_field_required as MemberField__m_field_required, MemberField_member_fields.m_field_public as MemberField__m_field_public, MemberField_member_fields.m_field_reg as MemberField__m_field_reg, MemberField_member_fields.m_field_cp_reg as MemberField__m_field_cp_reg, MemberField_member_fields.m_field_fmt as MemberField__m_field_fmt, MemberField_member_fields.m_field_show_fmt as MemberField__m_field_show_fmt, MemberField_member_fields.m_field_exclude_from_anon as MemberField__m_field_exclude_from_anon, MemberField_member_fields.m_field_order as MemberField__m_field_order, MemberField_member_fields.m_field_text_direction as MemberField__m_field_text_direction, MemberField_member_fields.m_field_settings as MemberField__m_field_settings, MemberField_member_fields.m_legacy_field_data as MemberField__m_legacy_field_data
	FROM (`exp_member_fields` as MemberField_member_fields)
	WHERE  (
	`MemberField_member_fields`.`m_legacy_field_data`  =  'n'
	)
	LIMIT 18446744073709551615</code></pre></div>
							<div class="query-file">
	#system/ee/legacy/libraries/Core.php L:475  Cp::set_default_view_variables() </div>
						</li>
															<li>
							<div class="query-time">
								0.0007s
								<i>203<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SELECT MemberGroup_member_groups.group_id as MemberGroup__group_id, MemberGroup_member_groups.site_id as MemberGroup__site_id, MemberGroup_member_groups.group_title as MemberGroup__group_title, MemberGroup_member_groups.group_description as MemberGroup__group_description, MemberGroup_member_groups.is_locked as MemberGroup__is_locked, MemberGroup_member_groups.menu_set_id as MemberGroup__menu_set_id, MemberGroup_member_groups.can_view_offline_system as MemberGroup__can_view_offline_system, MemberGroup_member_groups.can_view_online_system as MemberGroup__can_view_online_system, MemberGroup_member_groups.can_access_cp as MemberGroup__can_access_cp, MemberGroup_member_groups.can_access_footer_report_bug as MemberGroup__can_access_footer_report_bug, MemberGroup_member_groups.can_access_footer_new_ticket as MemberGroup__can_access_footer_new_ticket, MemberGroup_member_groups.can_access_footer_user_guide as MemberGroup__can_access_footer_user_guide, MemberGroup_member_groups.can_view_homepage_news as MemberGroup__can_view_homepage_news, MemberGroup_member_groups.can_access_files as MemberGroup__can_access_files, MemberGroup_member_groups.can_access_design as MemberGroup__can_access_design, MemberGroup_member_groups.can_access_addons as MemberGroup__can_access_addons, MemberGroup_member_groups.can_access_members as MemberGroup__can_access_members, MemberGroup_member_groups.can_access_sys_prefs as MemberGroup__can_access_sys_prefs, MemberGroup_member_groups.can_access_comm as MemberGroup__can_access_comm, MemberGroup_member_groups.can_access_utilities as MemberGroup__can_access_utilities, MemberGroup_member_groups.can_access_data as MemberGroup__can_access_data, MemberGroup_member_groups.can_access_logs as MemberGroup__can_access_logs, MemberGroup_member_groups.can_admin_design as MemberGroup__can_admin_design, MemberGroup_member_groups.can_delete_members as MemberGroup__can_delete_members, MemberGroup_member_groups.can_admin_mbr_groups as MemberGroup__can_admin_mbr_groups, MemberGroup_member_groups.can_admin_mbr_templates as MemberGroup__can_admin_mbr_templates, MemberGroup_member_groups.can_ban_users as MemberGroup__can_ban_users, MemberGroup_member_groups.can_admin_addons as MemberGroup__can_admin_addons, MemberGroup_member_groups.can_edit_categories as MemberGroup__can_edit_categories, MemberGroup_member_groups.can_delete_categories as MemberGroup__can_delete_categories, MemberGroup_member_groups.can_view_other_entries as MemberGroup__can_view_other_entries, MemberGroup_member_groups.can_edit_other_entries as MemberGroup__can_edit_other_entries, MemberGroup_member_groups.can_assign_post_authors as MemberGroup__can_assign_post_authors, MemberGroup_member_groups.can_create_entries as MemberGroup__can_create_entries, MemberGroup_member_groups.can_edit_self_entries as MemberGroup__can_edit_self_entries, MemberGroup_member_groups.can_delete_self_entries as MemberGroup__can_delete_self_entries, MemberGroup_member_groups.can_delete_all_entries as MemberGroup__can_delete_all_entries, MemberGroup_member_groups.can_view_other_comments as MemberGroup__can_view_other_comments, MemberGroup_member_groups.can_edit_own_comments as MemberGroup__can_edit_own_comments, MemberGroup_member_groups.can_delete_own_comments as MemberGroup__can_delete_own_comments, MemberGroup_member_groups.can_edit_all_comments as MemberGroup__can_edit_all_comments, MemberGroup_member_groups.can_delete_all_comments as MemberGroup__can_delete_all_comments, MemberGroup_member_groups.can_moderate_comments as MemberGroup__can_moderate_comments, MemberGroup_member_groups.can_send_cached_email as MemberGroup__can_send_cached_email, MemberGroup_member_groups.can_email_member_groups as MemberGroup__can_email_member_groups, MemberGroup_member_groups.can_email_from_profile as MemberGroup__can_email_from_profile, MemberGroup_member_groups.can_view_profiles as MemberGroup__can_view_profiles, MemberGroup_member_groups.can_edit_html_buttons as MemberGroup__can_edit_html_buttons, MemberGroup_member_groups.can_delete_self as MemberGroup__can_delete_self, MemberGroup_member_groups.mbr_delete_notify_emails as MemberGroup__mbr_delete_notify_emails, MemberGroup_member_groups.can_post_comments as MemberGroup__can_post_comments, MemberGroup_member_groups.exclude_from_moderation as MemberGroup__exclude_from_moderation, MemberGroup_member_groups.can_search as MemberGroup__can_search, MemberGroup_member_groups.search_flood_control as MemberGroup__search_flood_control, MemberGroup_member_groups.can_send_private_messages as MemberGroup__can_send_private_messages, MemberGroup_member_groups.prv_msg_send_limit as MemberGroup__prv_msg_send_limit, MemberGroup_member_groups.prv_msg_storage_limit as MemberGroup__prv_msg_storage_limit, MemberGroup_member_groups.can_attach_in_private_messages as MemberGroup__can_attach_in_private_messages, MemberGroup_member_groups.can_send_bulletins as MemberGroup__can_send_bulletins, MemberGroup_member_groups.include_in_authorlist as MemberGroup__include_in_authorlist, MemberGroup_member_groups.include_in_memberlist as MemberGroup__include_in_memberlist, MemberGroup_member_groups.cp_homepage as MemberGroup__cp_homepage, MemberGroup_member_groups.cp_homepage_channel as MemberGroup__cp_homepage_channel, MemberGroup_member_groups.cp_homepage_custom as MemberGroup__cp_homepage_custom, MemberGroup_member_groups.can_upload_new_files as MemberGroup__can_upload_new_files, MemberGroup_member_groups.can_edit_files as MemberGroup__can_edit_files, MemberGroup_member_groups.can_delete_files as MemberGroup__can_delete_files, MemberGroup_member_groups.can_upload_new_toolsets as MemberGroup__can_upload_new_toolsets, MemberGroup_member_groups.can_edit_toolsets as MemberGroup__can_edit_toolsets, MemberGroup_member_groups.can_delete_toolsets as MemberGroup__can_delete_toolsets, MemberGroup_member_groups.can_create_upload_directories as MemberGroup__can_create_upload_directories, MemberGroup_member_groups.can_edit_upload_directories as MemberGroup__can_edit_upload_directories, MemberGroup_member_groups.can_delete_upload_directories as MemberGroup__can_delete_upload_directories, MemberGroup_member_groups.can_create_channels as MemberGroup__can_create_channels, MemberGroup_member_groups.can_edit_channels as MemberGroup__can_edit_channels, MemberGroup_member_groups.can_delete_channels as MemberGroup__can_delete_channels, MemberGroup_member_groups.can_create_channel_fields as MemberGroup__can_create_channel_fields, MemberGroup_member_groups.can_edit_channel_fields as MemberGroup__can_edit_channel_fields, MemberGroup_member_groups.can_delete_channel_fields as MemberGroup__can_delete_channel_fields, MemberGroup_member_groups.can_create_statuses as MemberGroup__can_create_statuses, MemberGroup_member_groups.can_delete_statuses as MemberGroup__can_delete_statuses, MemberGroup_member_groups.can_edit_statuses as MemberGroup__can_edit_statuses, MemberGroup_member_groups.can_create_categories as MemberGroup__can_create_categories, MemberGroup_member_groups.can_create_member_groups as MemberGroup__can_create_member_groups, MemberGroup_member_groups.can_delete_member_groups as MemberGroup__can_delete_member_groups, MemberGroup_member_groups.can_edit_member_groups as MemberGroup__can_edit_member_groups, MemberGroup_member_groups.can_create_members as MemberGroup__can_create_members, MemberGroup_member_groups.can_edit_members as MemberGroup__can_edit_members, MemberGroup_member_groups.can_create_new_templates as MemberGroup__can_create_new_templates, MemberGroup_member_groups.can_edit_templates as MemberGroup__can_edit_templates, MemberGroup_member_groups.can_delete_templates as MemberGroup__can_delete_templates, MemberGroup_member_groups.can_create_template_groups as MemberGroup__can_create_template_groups, MemberGroup_member_groups.can_edit_template_groups as MemberGroup__can_edit_template_groups, MemberGroup_member_groups.can_delete_template_groups as MemberGroup__can_delete_template_groups, MemberGroup_member_groups.can_create_template_partials as MemberGroup__can_create_template_partials, MemberGroup_member_groups.can_edit_template_partials as MemberGroup__can_edit_template_partials, MemberGroup_member_groups.can_delete_template_partials as MemberGroup__can_delete_template_partials, MemberGroup_member_groups.can_create_template_variables as MemberGroup__can_create_template_variables, MemberGroup_member_groups.can_delete_template_variables as MemberGroup__can_delete_template_variables, MemberGroup_member_groups.can_edit_template_variables as MemberGroup__can_edit_template_variables, MemberGroup_member_groups.can_access_security_settings as MemberGroup__can_access_security_settings, MemberGroup_member_groups.can_access_translate as MemberGroup__can_access_translate, MemberGroup_member_groups.can_access_import as MemberGroup__can_access_import, MemberGroup_member_groups.can_access_sql_manager as MemberGroup__can_access_sql_manager, MemberGroup_member_groups.can_admin_channels as MemberGroup__can_admin_channels, MemberGroup_member_groups.can_manage_consents as MemberGroup__can_manage_consents
	FROM (`exp_member_groups` as MemberGroup_member_groups)
	WHERE  (
	`MemberGroup_member_groups`.`group_id`  =  1
	AND `MemberGroup_member_groups`.`site_id`  =  2
	)
	LIMIT 1</code></pre></div>
							<div class="query-file">
	#system/ee/legacy/libraries/Cp.php L:104  EllisLab\ExpressionEngine\Model\Member\Member::getCPHomepageURL() </div>
						</li>
															<li>
							<div class="query-time">
								0.0008s
								<i>206<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SELECT ee_m_MemberGroup_member_groups.group_id as ee_m_MemberGroup__group_id, ee_m_MemberGroup_member_groups.site_id as ee_m_MemberGroup__site_id, ee_m_MemberGroup_member_groups.group_title as ee_m_MemberGroup__group_title, ee_m_MemberGroup_member_groups.group_description as ee_m_MemberGroup__group_description, ee_m_MemberGroup_member_groups.is_locked as ee_m_MemberGroup__is_locked, ee_m_MemberGroup_member_groups.menu_set_id as ee_m_MemberGroup__menu_set_id, ee_m_MemberGroup_member_groups.can_view_offline_system as ee_m_MemberGroup__can_view_offline_system, ee_m_MemberGroup_member_groups.can_view_online_system as ee_m_MemberGroup__can_view_online_system, ee_m_MemberGroup_member_groups.can_access_cp as ee_m_MemberGroup__can_access_cp, ee_m_MemberGroup_member_groups.can_access_footer_report_bug as ee_m_MemberGroup__can_access_footer_report_bug, ee_m_MemberGroup_member_groups.can_access_footer_new_ticket as ee_m_MemberGroup__can_access_footer_new_ticket, ee_m_MemberGroup_member_groups.can_access_footer_user_guide as ee_m_MemberGroup__can_access_footer_user_guide, ee_m_MemberGroup_member_groups.can_view_homepage_news as ee_m_MemberGroup__can_view_homepage_news, ee_m_MemberGroup_member_groups.can_access_files as ee_m_MemberGroup__can_access_files, ee_m_MemberGroup_member_groups.can_access_design as ee_m_MemberGroup__can_access_design, ee_m_MemberGroup_member_groups.can_access_addons as ee_m_MemberGroup__can_access_addons, ee_m_MemberGroup_member_groups.can_access_members as ee_m_MemberGroup__can_access_members, ee_m_MemberGroup_member_groups.can_access_sys_prefs as ee_m_MemberGroup__can_access_sys_prefs, ee_m_MemberGroup_member_groups.can_access_comm as ee_m_MemberGroup__can_access_comm, ee_m_MemberGroup_member_groups.can_access_utilities as ee_m_MemberGroup__can_access_utilities, ee_m_MemberGroup_member_groups.can_access_data as ee_m_MemberGroup__can_access_data, ee_m_MemberGroup_member_groups.can_access_logs as ee_m_MemberGroup__can_access_logs, ee_m_MemberGroup_member_groups.can_admin_design as ee_m_MemberGroup__can_admin_design, ee_m_MemberGroup_member_groups.can_delete_members as ee_m_MemberGroup__can_delete_members, ee_m_MemberGroup_member_groups.can_admin_mbr_groups as ee_m_MemberGroup__can_admin_mbr_groups, ee_m_MemberGroup_member_groups.can_admin_mbr_templates as ee_m_MemberGroup__can_admin_mbr_templates, ee_m_MemberGroup_member_groups.can_ban_users as ee_m_MemberGroup__can_ban_users, ee_m_MemberGroup_member_groups.can_admin_addons as ee_m_MemberGroup__can_admin_addons, ee_m_MemberGroup_member_groups.can_edit_categories as ee_m_MemberGroup__can_edit_categories, ee_m_MemberGroup_member_groups.can_delete_categories as ee_m_MemberGroup__can_delete_categories, ee_m_MemberGroup_member_groups.can_view_other_entries as ee_m_MemberGroup__can_view_other_entries, ee_m_MemberGroup_member_groups.can_edit_other_entries as ee_m_MemberGroup__can_edit_other_entries, ee_m_MemberGroup_member_groups.can_assign_post_authors as ee_m_MemberGroup__can_assign_post_authors, ee_m_MemberGroup_member_groups.can_create_entries as ee_m_MemberGroup__can_create_entries, ee_m_MemberGroup_member_groups.can_edit_self_entries as ee_m_MemberGroup__can_edit_self_entries, ee_m_MemberGroup_member_groups.can_delete_self_entries as ee_m_MemberGroup__can_delete_self_entries, ee_m_MemberGroup_member_groups.can_delete_all_entries as ee_m_MemberGroup__can_delete_all_entries, ee_m_MemberGroup_member_groups.can_view_other_comments as ee_m_MemberGroup__can_view_other_comments, ee_m_MemberGroup_member_groups.can_edit_own_comments as ee_m_MemberGroup__can_edit_own_comments, ee_m_MemberGroup_member_groups.can_delete_own_comments as ee_m_MemberGroup__can_delete_own_comments, ee_m_MemberGroup_member_groups.can_edit_all_comments as ee_m_MemberGroup__can_edit_all_comments, ee_m_MemberGroup_member_groups.can_delete_all_comments as ee_m_MemberGroup__can_delete_all_comments, ee_m_MemberGroup_member_groups.can_moderate_comments as ee_m_MemberGroup__can_moderate_comments, ee_m_MemberGroup_member_groups.can_send_cached_email as ee_m_MemberGroup__can_send_cached_email, ee_m_MemberGroup_member_groups.can_email_member_groups as ee_m_MemberGroup__can_email_member_groups, ee_m_MemberGroup_member_groups.can_email_from_profile as ee_m_MemberGroup__can_email_from_profile, ee_m_MemberGroup_member_groups.can_view_profiles as ee_m_MemberGroup__can_view_profiles, ee_m_MemberGroup_member_groups.can_edit_html_buttons as ee_m_MemberGroup__can_edit_html_buttons, ee_m_MemberGroup_member_groups.can_delete_self as ee_m_MemberGroup__can_delete_self, ee_m_MemberGroup_member_groups.mbr_delete_notify_emails as ee_m_MemberGroup__mbr_delete_notify_emails, ee_m_MemberGroup_member_groups.can_post_comments as ee_m_MemberGroup__can_post_comments, ee_m_MemberGroup_member_groups.exclude_from_moderation as ee_m_MemberGroup__exclude_from_moderation, ee_m_MemberGroup_member_groups.can_search as ee_m_MemberGroup__can_search, ee_m_MemberGroup_member_groups.search_flood_control as ee_m_MemberGroup__search_flood_control, ee_m_MemberGroup_member_groups.can_send_private_messages as ee_m_MemberGroup__can_send_private_messages, ee_m_MemberGroup_member_groups.prv_msg_send_limit as ee_m_MemberGroup__prv_msg_send_limit, ee_m_MemberGroup_member_groups.prv_msg_storage_limit as ee_m_MemberGroup__prv_msg_storage_limit, ee_m_MemberGroup_member_groups.can_attach_in_private_messages as ee_m_MemberGroup__can_attach_in_private_messages, ee_m_MemberGroup_member_groups.can_send_bulletins as ee_m_MemberGroup__can_send_bulletins, ee_m_MemberGroup_member_groups.include_in_authorlist as ee_m_MemberGroup__include_in_authorlist, ee_m_MemberGroup_member_groups.include_in_memberlist as ee_m_MemberGroup__include_in_memberlist, ee_m_MemberGroup_member_groups.cp_homepage as ee_m_MemberGroup__cp_homepage, ee_m_MemberGroup_member_groups.cp_homepage_channel as ee_m_MemberGroup__cp_homepage_channel, ee_m_MemberGroup_member_groups.cp_homepage_custom as ee_m_MemberGroup__cp_homepage_custom, ee_m_MemberGroup_member_groups.can_upload_new_files as ee_m_MemberGroup__can_upload_new_files, ee_m_MemberGroup_member_groups.can_edit_files as ee_m_MemberGroup__can_edit_files, ee_m_MemberGroup_member_groups.can_delete_files as ee_m_MemberGroup__can_delete_files, ee_m_MemberGroup_member_groups.can_upload_new_toolsets as ee_m_MemberGroup__can_upload_new_toolsets, ee_m_MemberGroup_member_groups.can_edit_toolsets as ee_m_MemberGroup__can_edit_toolsets, ee_m_MemberGroup_member_groups.can_delete_toolsets as ee_m_MemberGroup__can_delete_toolsets, ee_m_MemberGroup_member_groups.can_create_upload_directories as ee_m_MemberGroup__can_create_upload_directories, ee_m_MemberGroup_member_groups.can_edit_upload_directories as ee_m_MemberGroup__can_edit_upload_directories, ee_m_MemberGroup_member_groups.can_delete_upload_directories as ee_m_MemberGroup__can_delete_upload_directories, ee_m_MemberGroup_member_groups.can_create_channels as ee_m_MemberGroup__can_create_channels, ee_m_MemberGroup_member_groups.can_edit_channels as ee_m_MemberGroup__can_edit_channels, ee_m_MemberGroup_member_groups.can_delete_channels as ee_m_MemberGroup__can_delete_channels, ee_m_MemberGroup_member_groups.can_create_channel_fields as ee_m_MemberGroup__can_create_channel_fields, ee_m_MemberGroup_member_groups.can_edit_channel_fields as ee_m_MemberGroup__can_edit_channel_fields, ee_m_MemberGroup_member_groups.can_delete_channel_fields as ee_m_MemberGroup__can_delete_channel_fields, ee_m_MemberGroup_member_groups.can_create_statuses as ee_m_MemberGroup__can_create_statuses, ee_m_MemberGroup_member_groups.can_delete_statuses as ee_m_MemberGroup__can_delete_statuses, ee_m_MemberGroup_member_groups.can_edit_statuses as ee_m_MemberGroup__can_edit_statuses, ee_m_MemberGroup_member_groups.can_create_categories as ee_m_MemberGroup__can_create_categories, ee_m_MemberGroup_member_groups.can_create_member_groups as ee_m_MemberGroup__can_create_member_groups, ee_m_MemberGroup_member_groups.can_delete_member_groups as ee_m_MemberGroup__can_delete_member_groups, ee_m_MemberGroup_member_groups.can_edit_member_groups as ee_m_MemberGroup__can_edit_member_groups, ee_m_MemberGroup_member_groups.can_create_members as ee_m_MemberGroup__can_create_members, ee_m_MemberGroup_member_groups.can_edit_members as ee_m_MemberGroup__can_edit_members, ee_m_MemberGroup_member_groups.can_create_new_templates as ee_m_MemberGroup__can_create_new_templates, ee_m_MemberGroup_member_groups.can_edit_templates as ee_m_MemberGroup__can_edit_templates, ee_m_MemberGroup_member_groups.can_delete_templates as ee_m_MemberGroup__can_delete_templates, ee_m_MemberGroup_member_groups.can_create_template_groups as ee_m_MemberGroup__can_create_template_groups, ee_m_MemberGroup_member_groups.can_edit_template_groups as ee_m_MemberGroup__can_edit_template_groups, ee_m_MemberGroup_member_groups.can_delete_template_groups as ee_m_MemberGroup__can_delete_template_groups, ee_m_MemberGroup_member_groups.can_create_template_partials as ee_m_MemberGroup__can_create_template_partials, ee_m_MemberGroup_member_groups.can_edit_template_partials as ee_m_MemberGroup__can_edit_template_partials, ee_m_MemberGroup_member_groups.can_delete_template_partials as ee_m_MemberGroup__can_delete_template_partials, ee_m_MemberGroup_member_groups.can_create_template_variables as ee_m_MemberGroup__can_create_template_variables, ee_m_MemberGroup_member_groups.can_delete_template_variables as ee_m_MemberGroup__can_delete_template_variables, ee_m_MemberGroup_member_groups.can_edit_template_variables as ee_m_MemberGroup__can_edit_template_variables, ee_m_MemberGroup_member_groups.can_access_security_settings as ee_m_MemberGroup__can_access_security_settings, ee_m_MemberGroup_member_groups.can_access_translate as ee_m_MemberGroup__can_access_translate, ee_m_MemberGroup_member_groups.can_access_import as ee_m_MemberGroup__can_access_import, ee_m_MemberGroup_member_groups.can_access_sql_manager as ee_m_MemberGroup__can_access_sql_manager, ee_m_MemberGroup_member_groups.can_admin_channels as ee_m_MemberGroup__can_admin_channels, ee_m_MemberGroup_member_groups.can_manage_consents as ee_m_MemberGroup__can_manage_consents
	FROM (`exp_member_groups` as ee_m_MemberGroup_member_groups)
	WHERE `ee_m_MemberGroup_member_groups`.`group_id` =  1
	LIMIT 18446744073709551615</code></pre></div>
							<div class="query-file">
	#system/ee/legacy/libraries/Core.php L:475  Cp::set_default_view_variables() </div>
						</li>
															<li>
							<div class="query-time">
								0.0003s
								<i>39<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SELECT Site_sites.site_id as Site__site_id, Site_sites.site_label as Site__site_label, Site_sites.site_name as Site__site_name, Site_sites.site_description as Site__site_description, Site_sites.site_bootstrap_checksums as Site__site_bootstrap_checksums, Site_sites.site_pages as Site__site_pages
	FROM (`exp_sites` as Site_sites)
	WHERE  (
	`Site_sites`.`site_id`  IN (2, 1)
	)
	LIMIT 18446744073709551615</code></pre></div>
							<div class="query-file">
	# L:  EllisLab\ExpressionEngine\Controller\Msm\Msm::index() </div>
						</li>
															<li>
							<div class="query-time">
								0.0003s
								<i>38<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SELECT COUNT(*) AS `numrows`
	FROM (`exp_config` as Config_config)
	WHERE  (
	`Config_config`.`site_id`  =  1
	AND `Config_config`.`key`  =  'is_site_on'
	AND `Config_config`.`value`  =  'y'
	)
	LIMIT 18446744073709551615</code></pre></div>
							<div class="query-file">
	# L:  EllisLab\ExpressionEngine\Controller\Msm\Msm::index() </div>
						</li>
															<li>
							<div class="query-time">
								0.0002s
								<i>38<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SELECT COUNT(*) AS `numrows`
	FROM (`exp_config` as Config_config)
	WHERE  (
	`Config_config`.`site_id`  =  2
	AND `Config_config`.`key`  =  'is_site_on'
	AND `Config_config`.`value`  =  'y'
	)
	LIMIT 18446744073709551615</code></pre></div>
							<div class="query-file">
	# L:  EllisLab\ExpressionEngine\Controller\Msm\Msm::index() </div>
						</li>
															<li>
							<div class="query-time">
								0.0004s
								<i>43<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SELECT MenuItem_menu_items.item_id as MenuItem__item_id, MenuItem_menu_items.parent_id as MenuItem__parent_id, MenuItem_menu_items.set_id as MenuItem__set_id, MenuItem_menu_items.name as MenuItem__name, MenuItem_menu_items.data as MenuItem__data, MenuItem_menu_items.type as MenuItem__type, MenuItem_menu_items.sort as MenuItem__sort, Set_menu_sets.set_id as Set__set_id, MemberGroups_member_groups.group_id as MemberGroups__group_id, MemberGroups_member_groups.site_id as MemberGroups__site_id, MemberGroups_member_groups.menu_set_id as MemberGroups__menu_set_id, Children_menu_items.item_id as Children__item_id, Children_menu_items.parent_id as Children__parent_id, Children_menu_items.set_id as Children__set_id, Children_menu_items.name as Children__name, Children_menu_items.data as Children__data, Children_menu_items.type as Children__type, Children_menu_items.sort as Children__sort
	FROM (`exp_menu_items` as MenuItem_menu_items)
	LEFT JOIN `exp_menu_sets` AS Set_menu_sets ON `Set_menu_sets`.`set_id` = `MenuItem_menu_items`.`set_id`
	LEFT JOIN `exp_member_groups` AS MemberGroups_member_groups ON `MemberGroups_member_groups`.`menu_set_id` = `Set_menu_sets`.`set_id`
	LEFT JOIN `exp_menu_items` AS Children_menu_items ON `Children_menu_items`.`parent_id` = `MenuItem_menu_items`.`item_id`
	WHERE  (
	`MemberGroups_member_groups`.`group_id`  =  1
	)
	ORDER BY `MenuItem_menu_items`.`sort`, `Children_menu_items`.`sort`
	LIMIT 18446744073709551615</code></pre></div>
							<div class="query-file">
	#system/ee/legacy/libraries/Cp.php L:291  EE_Menu::generate_menu() </div>
						</li>
															<li>
							<div class="query-time">
								0.0002s
								<i>38<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SELECT *
	FROM (`exp_sites`)
	ORDER BY `site_label`</code></pre></div>
							<div class="query-file">
	#system/ee/legacy/libraries/File_integrity.php L:27  Site_model::get_site() </div>
						</li>
															<li>
							<div class="query-time">
								0.0002s
								<i>39<abbr title="Kilobytes">KB</abbr></i>
							</div>
							<div class="query-wrap"><pre><code class="sql">SELECT MemberNewsView_member_news_views.news_id as MemberNewsView__news_id, MemberNewsView_member_news_views.version as MemberNewsView__version, MemberNewsView_member_news_views.member_id as MemberNewsView__member_id
	FROM (`exp_member_news_views` as MemberNewsView_member_news_views)
	WHERE  (
	`MemberNewsView_member_news_views`.`member_id`  =  1
	)
	LIMIT 1</code></pre></div>
							<div class="query-file">
	#system/ee/legacy/libraries/Cp.php L:250  Cp::shouldShowNewsButton() </div>
						</li>
								</ul>
				</div>
	</div>
		</div>
	</div>
