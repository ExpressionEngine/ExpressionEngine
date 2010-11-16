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
		
	<div class="heading"><h2 class="edit"><?=lang('accessories')?></h2></div>
	<div class="pageContents">
	<?php $this->load->view('_shared/message');?>	

		<?php
			$this->table->set_template($cp_table_template);
			$this->table->set_heading(array('data' => lang('accessory_name'), 'width' => '50%'), lang('available_to_member_groups'), lang('specific_page'), lang('status'));
			
			foreach ($accessories as $accessory)
			{
				$title = ($accessory['acc_pref_url']) ? "<a href='{$accessory['acc_pref_url']}'>{$accessory['name']}</a>" : $accessory['name'];

				$this->table->add_row(
										"<strong>{$title}</strong> ({$accessory['version']})<br />{$accessory['description']}",
										(count($accessory['acc_member_groups']) > 0) ? ul($accessory['acc_member_groups'], array('style'=>'list-style:disc!important; margin-left: 15px;')) : '',
										(count($accessory['acc_controller']) > 0) ? ul($accessory['acc_controller'], array('style'=>'list-style:disc!important; margin-left: 15px;')) : '',
										"<a href='{$accessory['acc_install']['href']}'>{$accessory['acc_install']['title']}</a>"
									);
		
			}
			
			echo $this->table->generate();
		?>
	
		</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file index.php */
/* Location: ./themes/cp_themes/default/accessories/index.php */