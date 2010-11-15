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
	<div class="contents">

		<div class="heading"><h2 class="edit"><?=$cp_page_title?></h2></div>
	    <div class="pageContents">
	
			<h3><?=$entry_title?></h3>
			
			
			<?=$entry_contents?>
			<div id="view_content_entry_links">
				<ul class="bullets">
			<?php if ($show_edit_link !== FALSE):?>
				<li><a href="<?=$show_edit_link?>"><?=$this->lang->line('edit_this_entry')?></a></li>
			<?php endif;?>

			<?php if ($filter_link !== FALSE):?>
				<li><a href="<?=$filter_link?>"><?=$this->lang->line('view_filtered')?></a></li></li>
			<?php endif;?>

			<?php if ($show_comments_link !== FALSE):?>
				<li><a href="<?=$show_comments_link?>"><?=$this->lang->line('view_comments')." ({$comment_count})"?></a></li>
			<?php endif;?>
			
			<?php if ($live_look_link !== FALSE):?>
				<li><a href="<?=$live_look_link?>"><?=$this->lang->line('live_look')?></a></li>
			<?php endif;?>
				</ul>
			</div>
		</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file message.php */
/* Location: ./themes/cp_themes/default/content/message.php */