</div>

<div id="footer">
	<a rel="external" href="<?=$this->cp->masked_url('http://ellislab.com')?>"><img src="<?=$cp_theme_url?>images/ee_logo_footer.gif" alt="<?=APP_NAME?>" width="109" height="15" /></a>
	<?=(IS_CORE)?' Core':''?> v<?=APP_VER?> <?php echo ' - ';
	echo lang('build'). '&nbsp;'.APP_BUILD;?> - &copy; <?=lang('copyright')?> 2003 - <?= date('Y') ?> <a href="<?=$this->cp->masked_url('http://ellislab.com/')?>" rel="external">EllisLab, Inc.</a><br />

	<?php
		echo str_replace("%x", $this->benchmark->elapsed_time('total_execution_time_start', 'total_execution_time_end'), lang('page_rendered'));
		echo ' - ';
		echo str_replace("%x", $this->db->query_count, lang('queries_executed'));
	?>
</div> <!-- footer -->


<div id="idle-modal" class="pageContents">
	<p id="idle-description" class="shun"><?=lang('session_idle_description')?></p>

	<p class="idle-fourth"><strong><?=lang('user_label')?></strong></p>

	<?=form_open('C=login&M=authenticate')?>
	<div class="idle-three-fourths shun">
		<p class="idle-fourth">
			<img src="<?=$cp_avatar_path ? $cp_avatar_path : $cp_theme_url.'images/site_logo.gif'?>" width="50" alt="<?=lang('user_avatar_alt')?>" />
		</p>
		<p class="idle-three-fourths">
			<p id="idle-screen-name"><?=$cp_screen_name?></p>
			<input type="hidden" name="username" value="<?=form_prep($this->session->userdata('username'))?>" />
			<span class="idle-member-group"><?=$this->session->userdata('group_title')?></span>
		</p>
	</div>

	<div class="idle-fourth">
		<p><label for="logout-confirm-password"><?=lang('password_label')?></label></p>
	</div>
	<div class="idle-three-fourths shun">
		<p><input type="password" name="password" class="field" id="logout-confirm-password"/></p>
	</div>

	<p id="idle-button-group">
		<a href="<?=BASE.AMP.'C=login&M=logout'?>"><?=sprintf(lang('session_idle_not_name'), $cp_screen_name)?></a> &nbsp;
		<input type="submit" class="submit" id="idle-login-button" value="<?=lang('login')?>" />
	</p>
	<?=form_close()?>
</div>

<div id="notice_container">
	<div id="notice_texts_container">
		<a id="close_notice" href="javascript:jQuery.ee_notice.destroy();">&times;</a>

		<div class="notice_texts notice_success"></div>
		<div class="notice_texts notice_alert"></div>
		<div class="notice_texts notice_error"></div>
		<div class="notice_texts notice_custom"></div>
	</div>
	<div id="notice_flag">
		<p id="notice_counts">
			<span class="notice_success"><img src="<?=$cp_theme_url?>images/success.png" alt="" width="14" height="14" /></span>
			<span class="notice_alert"><img src="<?=$cp_theme_url?>images/alert.png" alt="" width="14" height="14" /></span>
			<span class="notice_error"><img src="<?=$cp_theme_url?>images/error.png" alt="" width="14" height="14" /></span>
			<span class="notice_info"><img src="<?=$cp_theme_url?>images/info.png" alt="" width="14" height="14" /></span>
		</p>
	</div>
</div>

<?php
/* Moved to header to give third parties time to deprecate

<?php
if (isset($cp_global_js))
{
	echo $cp_global_js;
} ?>

<?=$this->view->script_tag('jquery/jquery.js')?>

*/
?>

<?php

echo $this->cp->render_footer_js();

if (isset($library_src))
{
	echo $library_src;
}

if (isset($script_foot))
{
	echo $script_foot;
}

foreach ($this->cp->footer_item as $item)
{
	echo $item."\n";
}
?>

</body>
</html>
<?php
/* End of file footer.php */
/* Location: ./themes/cp_themes/default/_shared/footer.php */