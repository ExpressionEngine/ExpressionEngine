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
			<?=lang('view_email_cache')?></h2>
		</div>
			<div class="pageContents">
			<?php if ($cached_email === FALSE): ?>
				<p class="notice"><?=lang('no_cached_email')?></p>
			<?php else: ?>

				<?php $this->load->view('_shared/message');?>


				<?=form_open('C=tools_communicate'.AMP.'M=delete_emails_confirm')?>
				<?php
					$this->table->set_template($cp_pad_table_template);
					$this->table->set_heading(
												lang('email_title'), 
												lang('email_date'),
												lang('total_recipients'),
												lang('status'),
												lang('resend'),
												form_checkbox('select_all', 'true', FALSE, 'class="toggle_all"')
											);

					foreach ($cached_email as $email)
					{
						$this->table->add_row(
												"<strong><a href='".BASE.AMP.'C=tools_communicate'.AMP.'M=view_email'.AMP.'id='.$email['cache_id']."'>{$email['email_title']}</a></strong>",
												$email['email_date'],
												$email['total_sent'],
												($email['status'] === TRUE) ? lang('complete') :
																			lang('incomplete').NBS.NBS.'<a href="'.BASE.AMP.'C=tools_communicate'.AMP.'M=batch_send'.AMP.'id='.$email['cache_id'].'">'.lang('finish_sending').'</a>',
												'<a href="'.BASE.AMP.'C=tools_communicate'.AMP.'id='.$email['cache_id'].'">'.lang('resend').'</a>',
												'<input class="toggle" type="checkbox" name="email[]" value="'.$email['cache_id'].'" />'
											);
					}

					echo $this->table->generate();
				?>

				<div class="tableFooter">
					<div class="tableSubmit">
							<?=form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'submit'))?>
					</div>		
							<?php if ($pagination): ?>					
								<span class="js_hide"><?=$pagination?></span>
							<?php endif; ?>
								<span class="pagination" id="filter_pagination"></span>
				</div> <!-- tableFooter -->
			

				<?=form_close()?>

			<?php endif; ?>
		</div>
	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file view_cached_email.php */
/* Location: ./themes/cp_themes/default/tools/view_cached_email.php */