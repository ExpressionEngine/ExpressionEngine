
<div id="footer">
	<a rel="external" href="<?=$this->config->item('base_url').$this->config->item('index_page')?>?URL=http://expressionengine.com/"><img src="<?=$cp_theme_url?>images/ee_logo_footer.gif" alt="<?=APP_NAME?>" width="109" height="15" /></a>
	<?=(IS_FREELANCER)?' Freelancer':''?> v<?=APP_VER?> - &copy; <?=lang('copyright')?> 2003 - 2009 <a href="http://ellislab.com/">EllisLab, Inc.</a><br />
	
	<?php
		echo str_replace("%x", $this->benchmark->elapsed_time('total_execution_time_start', 'total_execution_time_end'), lang('page_rendered'));
		echo ' - ';
		echo str_replace("%x", $this->db->query_count, lang('queries_executed'));
		echo ' - ';
		echo lang('build'). ' &nbsp;&nbsp;'.APP_BUILD;
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
</body>
</html>
<?php
/* End of file footer.php */
/* Location: ./themes/cp_themes/default/_shared/footer.php */