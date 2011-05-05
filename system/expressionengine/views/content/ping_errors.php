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
	
			<?php if (isset($ping_errors) and is_array($ping_errors)):?>
			<?=lang('xmlrpc_ping_errors')?>
			<ul>
				<?php foreach($ping_errors as $v):?>
				<li><?=$v['0']?> - <?=$v['1']?></li>
				<?php endforeach;?>
			</ul>
			<?php endif;?>

			<p><a href="<?=$entry_link?>"><?=lang('click_to_view_your_entry')?></a></p>

		</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file ping_errors.php */
/* Location: ./themes/cp_themes/default/content/ping_errors.php */