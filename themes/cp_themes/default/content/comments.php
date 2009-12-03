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

		<div class="heading">
			<h2 class="edit">
			<span id="filter_ajax_indicator" style="visibility:hidden; float:right;"><img src="<?=$cp_theme_url?>images/indicator2.gif" style="padding-right:20px;" /></span>
			<?=$cp_page_title?></h2>			
		</div>

		<div class="pageContents">
		<?php 
			$this->load->view('_shared/message');
			if (isset($comments)):
		?>

		<?=form_open('C=content_edit'.AMP.'M=modify_comments', array('name' => 'target', 'id' => 'target'), $hidden)?>

		<?php
			$this->table->set_template($cp_pad_table_template);

			$heading = array(
				lang('comment'),
				lang('channel'),
				lang('view_entry'),
				lang('author'),
				lang('email'),
				lang('date'),
				lang('comment_ip'),
				lang('status'),
				array('data' => form_checkbox('toggle_comments', 'true', FALSE, 'class="toggle_comments"'), 'style' => 'width: 5%;')
			);

			if ($validate !== TRUE)
			{
				unset($heading[1], $heading[2]);
			}
			$this->table->set_heading($heading);
			
			if (count($comments) > 0)
			{
				foreach ($comments as $comment)
				{
					$row = array(
						"<a class='less_important_link' href='{$comment['edit_url']}'>{$comment['comment']}</a>",
						$comment['channel_name'],
						($comment['show_link']) ? "<a class='less_important_link' href='{$comment['entry_url']}'>{$comment['entry_title']}</a>" : $comment['entry_title'],
						($comment['author_id'] == '0') ? $comment['name'] : "<a class='less_important_link'  href='{$comment['mid_search']}'>{$comment['name']}</a>",
						$comment['email'],
						$comment['date'],
						"<a class='less_important_link' href='{$comment['ip_search_url']}'>{$comment['ip_address']}</a>",
						"<a class='less_important_link' href='{$comment['status_change_url']}'>{$comment['status_label']}</a>",												
						form_checkbox('toggle[]', 'c'.$comment['comment_id'], FALSE, 'class="comment_toggle"')
					);

					if ($validate !== TRUE)
					{
						unset($row[1], $row[2]);
					}

					$this->table->add_row($row);
				}
			}
			
			echo $this->table->generate();
			?>

			<div class="tableSubmit">
				<?=form_submit('submit', lang('submit'), 'class="submit"').NBS.NBS?>
				<?=form_dropdown('action', $form_options).NBS.NBS?>
			</div>
			
			<span class="js_hide"><?=$pagination?></span>
			<span class="pagination" id="filter_pagination"></span>
			
				<div class="clear_left"></div>
		</div>
		<?=form_close()?>

		<?php else:?>

			<p><?=lang('no_comments')?></p>

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