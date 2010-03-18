<?php foreach($categories as $key => $val):?>
	<?php $first = current($val)?>
	
	<?php if (count($categories) > 1):?>
		<?=form_fieldset($key)?>
	<?php endif;?>
	
		<div id="cat_group_container_<?=$first[2]?>" class="cat_group_container">
	
			<?php foreach($val as $v):?>
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


<?php if ($edit_categories_link !== FALSE):?>
	
	<p style="margin: 15px;">
		
		<?php if (count($edit_categories_link) == 1):?>
			<a href="<?=$edit_categories_link[0]['url']?>" class="edit_categories_link"><?=$this->lang->line('edit_categories')?></a>
		<?php else:?>
			<?=$this->lang->line('edit_categories')?>: 
			
			<?php foreach ($edit_categories_link as $i => $link):?>
				<a href="<?=BASE.$link['url']?>" class="edit_categories_link"><?=$link['group_name']?></a><?=($i < count($edit_categories_link) - 1) ? ', ' : ''?>
			<?php endforeach;?>
			
		<?php endif;?>
	
	</p>
	
<?php endif;?>


<?php
/* End of file categories.php */
/* Location: ./themes/cp_themes/default/content/_assets/categories.php */