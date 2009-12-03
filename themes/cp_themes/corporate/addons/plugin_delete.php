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

		<div class="heading"><h2><?=lang('plugin_delete_confirm')?></h2></div>

		<div class="pageContents">
			
			<?=form_open('C=addons_plugins'.AMP.'M=remove')?>

			<p class="notice"><?=lang($message)?></p>

			<?php
				foreach($hidden as $plugin)
				{
					?>
				<ul class="subtext">
					<li>&lsquo;&nbsp;<?=lang($plugin)?>&nbsp;&rsquo;</li>
				</ul>
			<?php
					echo form_hidden('deleted[]', $plugin);
				}
			?>

			<p class="notice"><?=lang('action_can_not_be_undone')?></p>
			
			<p><?=form_submit('submit', lang('plugin_remove'), 'class="delete"')?></p>

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

/* End of file plugin_delete.php */
/* Location: ./themes/cp_themes/corporate/addons/plugin_delete.php */