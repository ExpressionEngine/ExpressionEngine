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

			<div class="heading"><h2 class="edit"><?=lang('translation')?></h2></div>
			<div class="pageContents">

			<?php $this->load->view('_shared/message');?>
			<?php if ( ! $language_list): ?>
				<p class="notice"><?=lang('no_lang_keys')?></p>
			<?php else: ?>	
			<?=form_open('C=tools_utilities'.AMP.'M=translation_save', '', $form_hidden)?>

			<?php
			$this->table->set_template($cp_pad_table_template);
			$this->table->set_heading(
										array('data'=>lang('english'), 'class'=>'translatePhrase'),
										lang('translation')
									);
									
			foreach ($language_list as $label => $value)
			{
				$this->table->add_row(
					array('data' => form_label($value['original'], $label), 'style' => 'text-align:right;'),
					form_input(array('id' => $label,
						 'name' => $label,
						 'value' => $value['trans'],
						 'class'=>'field translate_field'))
				);
			}
				
			echo $this->table->generate();
			?>
			
			<p><?=form_submit('translate', lang('update'), 'class="submit"')?></p>

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

/* End of file translate.php */
/* Location: ./themes/cp_themes/default/tools/translate.php */