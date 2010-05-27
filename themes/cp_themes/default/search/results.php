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
				<?php if ($can_rebuild):?>
				
					<div class="cp_button"><a href="<?=BASE.AMP.'C=search'.AMP.'M=build_index'?>"><?=lang('rebuild_search_index')?></a></div>
					<div class="clear_left"></div>
				<?php endif;

				if ($num_rows > 0):
			
					$list = array();
				
					foreach ($search_data as $data)
					{
						$list[] = "<a href='{$data['url']}'>{$data['name']}</a>";
					}
				?>

					<?=ul($list, array('class' => 'bullets'))?>

				<?php else:?>

					<p><?=lang('no_search_results')?></p>
					<p><?=lang('searched_for')?> <?=$keywords?></p>

				<?php endif;?>
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
/* Location: ./themes/cp_themes/default/search/index.php */