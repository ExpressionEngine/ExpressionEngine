<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="home" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a href="<?=BASE.AMP?>C=design" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>
	
	<div class="container pad">
	
		<?php
			$this->table->set_template(array('table_open' => '<table class="mainTable clear_left" cellspacing="0" cellpadding="0">'));
			$this->table->set_heading(
										lang('snippets'),
										lang('snippet_syntax'),
										lang('delete')
									);
									
			if ($snippets_count >= 1)
			{
				foreach ($snippets->result() as $variable)
				{
					$this->table->add_row(
						'<a href="'.BASE.AMP.'C=design'.AMP.'M=snippets_edit'.AMP.'snippet='.$variable->snippet_name.'">'.$variable->snippet_name.'</a>', 
						'{'.$variable->snippet_name.'}', 
						'<a href="'.BASE.AMP.'C=design'.AMP.'M=snippets_delete'.AMP.'snippet_id='.$variable->snippet_id.'">'.lang('delete').'</a>'
					);
				}
			}
			else
			{
				$this->table->add_row(array('data' => lang('no_snippets'), 'colspan' => 3));
			}
			
			echo $this->table->generate();
		?>
	</div>
</div>
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file snippets.php */
/* Location: ./themes/cp_themes/mobile/design/snippets.php */