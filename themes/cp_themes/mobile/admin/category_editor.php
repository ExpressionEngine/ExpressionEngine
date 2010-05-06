<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=admin_content&amp;M=category_management" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
		<?php $this->load->view('_shared/right_nav')?>
		<?php $this->load->view('_shared/message');?>
		
		<?=form_open($form_action)?>
		<?php
		
		if (count($categories) > 0)
		{
			$up		= '<img src="'.PATH_CP_GBL_IMG.'arrow_up.gif" border="0"  width="16" height="16" alt="" title="" />';
			$down	= '<img src="'.PATH_CP_GBL_IMG.'arrow_down.gif" border="0"  width="16" height="16" alt="" title="" />';			

			foreach ($categories as $category):?>
		 	<div class="label">
				<div style="float:left">
				<strong><?=lang('id')?>:</strong> <?=$category[0]?><br />
				<strong><?=lang('category_name')?>:</strong> <?=$category[1]?><br />
				</div>
				<?php
				
				$this->load->helper('url');
				
				$link = anchor(BASE.AMP.'C=admin_content'.AMP.'M=change_category_order'.AMP.'cat_id='.$category['0'].AMP.'group_id='.$group_id.AMP.'order=up', $up).NBS.							anchor(BASE.AMP.'C=admin_content'.AMP.'M=change_category_order'.AMP.'cat_id='.$category['0'].AMP.'group_id='.$group_id.AMP.'order=down', $down);
				$spcr = '<img src="'.PATH_CP_GBL_IMG.'clear.gif" border="0"  width="24" height="14" alt="" title="" />';
				$cat_marker = '<img src="'.PATH_CP_GBL_IMG.'cat_marker.gif" border="0"  width="18" height="14" alt="" title="" />';
				?>
				<div style="float:right">
				<?=$link?>
				</div>
				<div class="clear"></div>
			</div>
			<ul class="rounded">
				<li><a href="<?=BASE.AMP.'C=admin_content'.AMP.'M=category_edit'.AMP.'cat_id='.$category[0].AMP.'group_id='.$group_id?>"><?=lang('edit')?></a></li>
				<li><a href="<?=BASE.AMP.'C=admin_content'.AMP.'M=category_delete_conf'.AMP.'cat_id='.$category[0].AMP.'group_id='.$group_id?>"><?=lang('delete')?></a></li>
				
			</ul>
			<?php endforeach;
			
		}
		
		?>
		
		<div class="container pad">
			<?=lang('global_sort_order')?><br />
			<?=form_radio('sort_order', 'a', (($sort_order == 'a') ? TRUE : FALSE)).NBS.lang('alpha').NBS.NBS.form_radio('sort_order', 'c', (($sort_order == 'c') ? TRUE : FALSE)).NBS.lang('custom').NBS.NBS.NBS?>

		</div>
		
		<?=form_submit('submit', lang('update'), 'class="whiteButton"')?>	

		<?=form_close()?>

</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file category_editor.php */
/* Location: ./themes/cp_themes/default/admin/category_editor.php */