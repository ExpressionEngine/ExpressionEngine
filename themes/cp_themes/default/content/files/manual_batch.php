<?php
if ( ! $EE_view_disable)
{
	$this->load->view('_shared/header');
	$this->load->view('_shared/main_menu');
	$this->load->view('_shared/sidebar');
	$this->load->view('_shared/breadcrumbs');
}
?>

<div id="mainContent">
	<?php $this->load->view('_shared/right_nav')?>
	<div class="contents">

		<div class="heading">
			<h2 class="edit"><?=lang('batch_upload')?></h2>
		</div>
		
		<div class="pageContents">
			<?php if ($files_count === 0): ?>
			<p><?=lang('no_results')?></p>
			<?php else: ?>
				<h4><?=$count_lang?></h4>
				<?=form_open('.')?>
				
				
				</form>
			<?php endif; ?>
		</div>
	</div>
</div> 

<?php
if ( ! $EE_view_disable)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file sync.php */
/* Location: ./themes/cp_themes/default/tools/sync.php */