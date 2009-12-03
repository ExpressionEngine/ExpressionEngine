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

		<div class="heading"><h2><?=$cp_page_title?></h2></div>

		<div class="pageContents">

			<?php $this->load->view('_shared/message');?>


			<?php
				$this->table->set_template($cp_pad_table_template);
				$this->table->set_heading(
											array('data' => lang('channel_id'), 'width' => '4%'),
											lang('channel_name'),
											lang('channel_short_name'),
											lang('edit_preferences'),
											lang('edit_groups'),
											array('data' => lang(''), 'width' => '4%', 'text-align' => 'center')
										);
										
				foreach ($channel_data->result() as $channel)
				{
					$this->table->add_row(
											$channel->channel_id,
											"<strong>{$channel->channel_title}</strong>",
											$channel->channel_name,
											'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=channel_edit'.AMP.'channel_id='.$channel->channel_id.'">'.lang('edit_preferences').'</a>',
											'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=channel_edit_group_assignments'.AMP.'channel_id='.$channel->channel_id.'">'.lang('edit_groups').'</a>',
											'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=channel_delete_confirm'.AMP.'channel_id='.$channel->channel_id.'"><img src="'.$cp_theme_url.'images/content_custom_tab_delete.gif" alt="'.lang('delete').'" width="19" height="18" /></a>'
										);
				}

				echo $this->table->generate();
			?>
		</div> <!-- pageContents -->
	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file channel_management.php */
/* Location: ./themes/cp_themes/corporate/admin/channel_management.php */