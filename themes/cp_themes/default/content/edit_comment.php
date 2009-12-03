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
				<?php $this->load->view('_shared/message');?>

				<?=form_open('C=content_edit'.AMP.'M=update_comment', '', $hidden)?>
				<?php
				$this->table->set_template($cp_pad_table_template);
				$this->table->set_heading(
				    array('data' => '&nbsp;', 'style' => 'width:50%;'),
				    '&nbsp;'
				);
				
				$required = '<em class="required">*&nbsp;</em>';
				
				if ($author_id == 0) 
				{
					$this->table->add_row(array(
							$required.lang('name', 'name'),
							form_input('name', $name, 'class="field"; name="name"; id="name"')
						)
					);
					
					$this->table->add_row(array(
							$required.lang('email', 'email'),
							form_input('email', $email, 'class="field"; name="email"; id="email"')
						)
					);
					
					$this->table->add_row(array(
							lang('url', 'url'),
							form_input('url', $url, 'class="field"; name="url"; id="url"')
						)
					);
					
					$this->table->add_row(array(
							lang('location', 'location'),
							form_input('location', $location, 'class="field"; name="location"; id="location"')
						)
					);
				}
				
				$this->table->add_row(array(
							$required.lang('comment', 'comment'),
							form_textarea('comment', $comment, 'class="field"; name="comment"; id="comment"')
					)
				);

				echo $this->table->generate();
				?>
				
				<p><?=form_submit('update_comment', lang('update'), 'class="submit"')?></p>
			
			<?=form_close()?>
			</div> <!-- pageContents -->
	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file view_members.php */
/* Location: ./themes/cp_themes/default/content/edit_comment.php */