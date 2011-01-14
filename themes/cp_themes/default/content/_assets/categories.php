<?php
$cat_action_buttons = FALSE;

// Handle a special case of empty category groups
if (count($edit_links) > count($categories))
{
	foreach($edit_links as $i => $group_info)
	{
		if ( ! isset($categories[$group_info['group_name']]))
		{
			$categories[$group_info['group_name']] = $group_info['url'];
		}
	}
}

?>



<?php foreach($categories as $key => $val):?>

	<?php if ( ! is_array($val))
	{
		$id = end(explode('group_id=', $val));
		$first = array(2 => $id);
	}
	else
	{
		$first = current($val);
	}
	?>
	
	<?php if (count($categories) > 1):?>
		<?=form_fieldset($key)?>
	<?php endif;?>
	
		<div id="cat_group_container_<?=$first[2]?>" class="cat_group_container">
	
			<?php if (is_array($val))
				foreach($val as $v):?>
				<label>
					<?=repeater(NBS.NBS.NBS.NBS, $v[5] - 1)?>
					<?=form_checkbox('category[]', $v[0], $v[4]).NBS.NBS.$v[1]?>
				</label>
			<?php endforeach;?>
	
			<div class="cat_action_buttons" style="float: right; display: none;">
				<a href="<?=BASE.AMP.'C=admin_content'.AMP.'M=category_edit'.AMP.'group_id='.$first[2]?>" class="add_cat"><?=lang('add_category')?></a> &nbsp; 
				<a href="#" class="cats_done"><?=lang('done')?></a>
			</div>
		</div>
		
	<?php if (count($categories) > 1):?>
		<?=form_fieldset_close()?>
	<?php endif;?>
	
<?php endforeach;?>


<?php if ($edit_links !== FALSE):?>
	
	<p style="margin: 15px;">
		
		<?php if (count($edit_links) == 1):?>
			<a href="<?=$edit_links[0]['url']?>" class="edit_categories_link"><?=$this->lang->line('edit_categories')?></a>
		<?php else:?>
			<?=$this->lang->line('edit_categories')?>: 
			
			<?php foreach ($edit_links as $i => $link):?>
				<a href="<?=BASE.$link['url']?>" class="edit_categories_link"><?=$link['group_name']?><?=($i < count($edit_links) - 1) ? ',' : ''?></a>&nbsp;
			<?php endforeach;?>
			
		<?php endif;?>
	
	</p>
	
<?php endif;?>


<?php
/* End of file categories.php */
/* Location: ./themes/cp_themes/default/content/_assets/categories.php */