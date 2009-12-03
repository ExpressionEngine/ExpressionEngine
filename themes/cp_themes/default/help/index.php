<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
	$this->load->view('_shared/main_menu');
	$this->load->view('_shared/sidebar');
	$this->load->view('_shared/breadcrumbs');
}
?>

<div id="mainContent"<?=$maincontent_state?>>
	<?php $this->load->view('_shared/right_nav')?>

	<div class="contentMenu">
		<div class="heading"><h2><?=lang('videos')?></h2></div>
		<ul class="videos">
			<li><a rel="external" href="http://expressionengine.com/tutorials/lesson/installing_expressionengine/"><?=lang('installing_ee')?></a></li>
			<li><a rel="external" href="http://expressionengine.com/tutorials/lesson/introduction_to_templates/"><?=lang('introduction_to_templates')?></a></li>
			<li><a rel="external" href="http://expressionengine.com/tutorials/lesson/weblogs_and_custom_fields/"><?=lang('channel_custom_fields')?></a></li>
			<li><a rel="external" href="http://expressionengine.com/tutorials/lesson/weblog_template_relationship/"><?=lang('channel_template_relationship')?></a></li>
		</ul>
	</div>

	<div class="contentMenu">
		<div class="heading"><h2><?=lang('community_tutorials')?></h2></div>
		<ul>
			<li><a rel="external" href="http://www.boyink.com/splaat/comments/building-an-expressionengine-site-chapter-1/"><?=lang('building_ee_site_01')?></a></li>
			<li><a rel="external" href="http://www.boyink.com/splaat/comments/designing-an-expressionengine-architecture/"><?=lang('designing_ee_architecture')?></a></li>
			<li><a rel="external" href="http://www.eehowto.com/howto/info/troubleshooting-problems-with-file-uploads/"><?=lang('troubleshooting_file_uploads')?></a></li>
			<li><a rel="external" href="http://expressionengine.com/docs/cp/index.html"><?=lang('ee_cp_overview')?></a></li>
			<li><a rel="external" href="http://loweblog.com/freelance/article/ee-search-bookmarklet/"><?=lang('ee_seach_bookmarklet')?></a></li>
		</ul>
	</div>

	<div class="contentMenu">
		<div class="heading"><h2><?=lang('support')?></h2></div>
		<ul>
			<li><a rel="external" href="http://expressionengine.com/docs/"><?=lang('documentation')?></a></li>
			<li><a rel="external" href="http://expressionengine.com/forums/"><?=lang('support_forums')?></a></li>
			<li><a rel="external" href="http://expressionengine.com/wiki/"><?=lang('wiki')?></a></li>
		</ul>
	</div>
	
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file index.php */
/* Location: ./themes/cp_themes/default/help/index.php */