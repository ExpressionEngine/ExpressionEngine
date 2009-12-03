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

			<div class="heading"><h2 class="edit"><?=lang('translation_tool')?></h2></div>
			<div class="pageContents">

				<?php $this->load->view('_shared/message');?>

				<?php if (isset($not_writeable)):?>
					<p class="notice"><?=$not_writeable?></p>
				<?php endif; ?>
				<p><?=lang('choose_translation_file')?></p>

				<ul class="menu_list">
				<?php foreach($language_files as $file):?>

					<li<?=alternator('', ' class="odd"');?>><a href="<?=BASE.AMP.'C=tools_utilities'.AMP.'M=translate'.AMP.'language_file='.$file?>"><?=$file?></a></li>

				<?php endforeach;?>
				</ul>		
			</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file translation_tool.php */
/* Location: ./themes/cp_themes/default/tools/translation_tool.php */