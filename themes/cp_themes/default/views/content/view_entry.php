<?php extend_template('default') ?>
	
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
		
		<?php if ($publish_another_link !== FALSE):?>
			<li><a href="<?=$publish_another_link?>"><?=$this->lang->line('publish_another_entry')?></a></li></li>
		<?php endif;?>
		
		<?php if ($show_comments_link !== FALSE):?>
			<li><a href="<?=$show_comments_link?>"><?=$this->lang->line('view_comments')." ({$comment_count})"?></a></li>
		<?php endif;?>
		
		<?php if ($live_look_link !== FALSE):?>
			<li><a href="<?=$live_look_link?>"><?=$this->lang->line('live_look')?></a></li>
		<?php endif;?>
		</ul>
	</div>