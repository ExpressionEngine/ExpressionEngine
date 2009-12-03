<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="member_group_manager" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=content_edit" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
	<?php $this->load->view('_shared/message');?>


		<?php if(count($cats) > 0):?>

		<?=form_open('C=content_edit'.AMP.'M=multi_entry_category_update', '', $form_hidden)?>

		<h3 class="pad"><?=$this->lang->line('categories')?></h3>

        <?php foreach ($cats as $key => $val):?>
		<div class="field">
			<label><?=$key?></label>
		</div>
		<ul>
		<?php foreach ($val as $v):?>
			<li><?php $indent = ($v['5'] != 1) ? repeater(NBS.NBS.NBS.NBS, $v['5']) : '';?>
			<?=$indent.form_checkbox('category[]', $v['0'], $v['4'], 'style="width:auto!important;"').NBS.NBS.$v['1']?><br />
			</li>
		<?php endforeach;?>
		</ul>
      <?php endforeach;?>

		<?php if ($edit_categories_link !== FALSE):?>

		<div>
		<?php if (count($edit_categories_link) == 1):?>
			<a rel="external" href="<?=$edit_categories_link['0']['url']?>"><?=lang('edit_categories')?></a>
		<?php else: ?>
			<?php foreach ($edit_categories_link as $link) : ?>
				<a rel="external" href="<?=$link['url']?>"><?=$link['group_name']?></a>, 
			<?php endforeach; endif;?>
		</div>
		<?php endif;?>
		
		<?=form_submit('update_entries', lang('update'), 'class="whiteButton"')?>

		<?=form_close()?>

	<?php endif; ?>
	

</div>

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file view_members.php */
/* Location: ./themes/cp_themes/default/members/member_group_manager.php */