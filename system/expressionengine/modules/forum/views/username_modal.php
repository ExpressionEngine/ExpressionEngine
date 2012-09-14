<?php extend_view('_wrapper') ?>

<div id="ajaxContent" style="display: none; text-align: center;">
	<p class="notice"><?=lang('forum_use_lookup_inst')?></p>
	<div class="shun">
	<input type="text" name="name" value="" id="name" size="30"><br /><br />
	<select name="filterby" id="filterby">
		<option value="username"><?=lang('forum_search_by_user')?></option>
		<option value="screen_name"><?=lang('forum_search_by_screen')?></option>
	</select>
	</div>
	
	<img src="<?=$cp_theme_url?>images/indicator.gif" id="spinner" style="display: none;" />
	<p id="member_search_error"></p>
	
	<div id="user_lookup_results" style="display: none;">
		<h3 class="shun"><?=lang('forum_moderator_search_res'); ?></h3>
		<table border="0" cellspacing="0" cellpadding="0" class="templateTable templateEditorTable">
			<thead>
			<tr>
				<th style="width: 50%;"><?=lang('forum_screen_name'); ?></th>
				<th style="width: 50%;"><?=lang('forum_username'); ?></th>
			</tr>
			</thead>
			<tbody>
				<tr></tr>
			</tbody>
		</table>
	</div>
</div>