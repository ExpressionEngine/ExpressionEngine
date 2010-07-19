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
	<?php if (lang('create_new_site')):?>
	<?php $this->load->view('_shared/right_nav')?>
	<?php endif; ?>
	<div class="contents">
		<div class="heading"><h2 class="edit"><?=$cp_page_title?></h2></div>
		<div class="pageContents">

			<?php $this->load->view('_shared/message');?>

			<?php if (lang('create_new_site')):?>
				<div class="clear_left"></div>
			<?php endif;?>

			<h4><?=lang('msm_product_name')?></h4>
			<p><?=lang('msm_version').$msm_version.'  '.lang('msm_build_number').$msm_build_number?></p>

			<?php
				$this->table->set_template($cp_pad_table_template);
				$this->table->set_heading(
											array('data' => lang('site_id'), 'width' => '7%'),
											lang('site_label'),
											lang('site_name'),
											lang('edit_site'),
											lang('delete')
										);
										
				foreach ($site_data->result() as $site)
				{
					$this->table->add_row(
											$site->site_id,
											"<strong>{$site->site_label}</strong>",
											$site->site_name,
											'<a href="'.BASE.AMP.'C=sites'.AMP.'M=add_edit_site'.AMP.'site_id='.$site->site_id.'">'.lang('edit_site').'</a>',
											
											($site->site_id == 1) ? '----' : '<a href="'.BASE.AMP.'C=sites'.AMP.'M=site_delete_confirm'.AMP.'site_id='.$site->site_id.'">'.lang('delete').'</a>'
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

/* End of file list_sites.php */
/* Location: ./themes/cp_themes/default/sites/list_sites.php */