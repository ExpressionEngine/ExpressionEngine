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

		<?php $this->load->view('_shared/message');?>

		<?php if(count($cats) > 0):?>

		<?=form_open('C=content_edit'.AMP.'M=multi_entry_category_update', '', $form_hidden)?>

		<h3><?=$this->lang->line('categories')?></h3>

        <?php foreach ($cats as $key => $val):?>
		<fieldset style="margin-bottom:25px"><legend><?=$key?></legend>
			<?php foreach ($val as $v):?>
				<?php $indent = ($v['5'] != 1) ? repeater(NBS.NBS.NBS.NBS, $v['5']) : '';    ?>
				<?=$indent.form_checkbox('category[]', $v['0'], $v['4'], 'style="width:auto!important;"').NBS.NBS.$v['1']?><br />
             <?php endforeach;?>
		</fieldset>
      <?php endforeach;?>

		<?php if ($edit_categories_link !== FALSE):?>

		<?php if (count($edit_categories_link) == 1):?>
			<a href="<?=$edit_categories_link['0']['url']?>"><?=lang('edit_categories')?></a>
		<?php else: ?>
			<?php
				$total_results = count($edit_categories_link);
				$count = 0;
			?>
			<p>
			<?php foreach ($edit_categories_link as $link) : ?>
				<?php $count++;?>
				<a href="<?=$link['url']?>"><?=$link['group_name']?></a>
				<?php if ($count != $total_results):?>, <?php endif;?> 
			<?php endforeach; endif;?>
			</p><br />
		<?php endif;?>
		
		<p><?=form_submit('update_entries', lang('update'), 'class="submit"')?></p>

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

/* End of file edit.php */
/* Location: ./themes/cp_themes/default/content/multi_cat_edit.php */
