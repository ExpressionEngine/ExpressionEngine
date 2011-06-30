</div>

<div id="footer">
	<a rel="external" href="<?=$this->cp->masked_url('http://expressionengine.com/')?>"><img src="<?=$cp_theme_url?>images/ee_logo_footer.gif" alt="<?=APP_NAME?>" width="109" height="15" /></a>
	<?=(IS_FREELANCER)?' Freelancer':''?> v<?=APP_VER?> <? echo ' - ';
	echo lang('build'). ' date&nbsp;&nbsp;'.APP_BUILD;?> - &copy; <?=lang('copyright')?> 2003 - 2011 <a href="<?=$this->cp->masked_url('http://ellislab.com/')?>" rel="external">EllisLab, Inc.</a><br />
	
	<?php
		echo str_replace("%x", $this->benchmark->elapsed_time('total_execution_time_start', 'total_execution_time_end'), lang('page_rendered'));
		echo ' - ';
		echo str_replace("%x", $this->db->query_count, lang('queries_executed'));
	?>
</div> <!-- footer -->

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

</body>
</html>
<?php
/* End of file footer.php */
/* Location: ./themes/cp_themes/default/_shared/footer.php */