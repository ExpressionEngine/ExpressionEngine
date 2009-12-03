<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="file_browser" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a class="back" href="<?=BASE.AMP?>"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>

	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');
	if (isset($comments)):
	?>

		<?=form_open('C=content_edit'.AMP.'M=modify_comments', array('name' => 'target', 'id' => 'target'), $hidden)?>

		<?php
			
			if (count($comments) > 0)
			{
				foreach ($comments as $comment):?>
					
					<div class="label">
						<?=lang('comment', 'comment')?>
					</div>
					<ul>
						<li><a href='<?=$comment['edit_url']?>'><?=$comment['comment']?></a></li>	
						<li><?php echo ($comment['author_id'] == '0') ? $comment['name'] : "<a class='less_important_link'  href='{$comment['mid_search']}'>{$comment['name']}</a>"?></li>
						<li><?=$comment['email']?></li>
						<li><?=$comment['date']?></li>
						<li><a href='<?=$comment['ip_search_url']?>'><?=$comment['ip_address']?></a></li>
						<li><a href='<?=$comment['status_change_url']?>'><?=$comment['status_label']?></a></li>
						<li><?=form_checkbox('toggle[]', 'c'.$comment['comment_id'], FALSE, 'class="comment_toggle"')?></li>
					</ul>
				<?php

				 endforeach;
			}
			?>
				<ul>
					<li><?=form_dropdown('action', $form_options)?></li>
				</ul>
				<?=form_submit('submit', lang('submit'), 'class="whiteButton"')?>
				<?=$pagination_links?>
		</div>
		<?=form_close()?>

		<?php else:?>

			<p class="container pad"><?=lang('no_comments')?></p>

		<?php endif;?>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file comments.php */
/* Location: ./themes/cp_themes/default/content/comments.php */