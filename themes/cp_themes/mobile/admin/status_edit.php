<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=admin_content&amp;M=status_group_management" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>

	<?php $this->load->view('_shared/right_nav')?>
	<div class="contents">

		<div class="heading"><h2 class="edit"><?=$cp_page_title?></h2></div>
		<div class="pageContents">

		<?=form_open('C=admin_content'.AMP.'M=status_update', '', $form_hidden)?>
		
		<ul>
			<?php
				// open and closed names not editable
				if ($status == 'open' OR $status == 'closed'):?>
				<li><?=lang('status_name')?> <?=lang($status);?></li>
				<?php else:?>
				<li><?=form_input(array(
								'id'		=> 'status',
								'name'		=> 'status',
								'class'		=> 'field',
								'value'		=> $status))?></li>
				<?php endif;?>	
				<li>
					<?=form_label(lang('status_order'), 'status_order')?>
					<?=form_input(array(
								'id'		=> 'status_order',
								'name'		=> 'status_order',
								'style'		=> 'float:right; margin-left:40%; width:40%',
								'value'		=> $status_order))?>
				</li>
		</ul>


		<p>
			<?=form_label(lang('status_order'), 'status_order')?>
			<?=form_input(array('id'=>'status_order','name'=>'status_order','class'=>'field','value'=>$status_order))?>
		</p>

		<p>
			<?=form_label(lang('highlight'), 'highlight')?>
			<?=form_input(array('id'=>'highlight','name'=>'highlight','class'=>'field','value'=>$highlight))?>
		</p>

		<?php if ($this->session->userdata('group_id') == 1):?>
		<h3><?=lang('restrict_status_to_group')?></h3>
		<?php
		$this->table->set_heading(
									lang('member_group'),
									lang('can_edit_status')
								);

		if (count($member_perms) > 0)
		{
			foreach ($member_perms as $row)
			{
				 $this->table->add_row(
					$row['group_title'],
						form_radio('access_'.$row['group_id'], 'y', $row['access_y'], 'id="access_'.$row['group_id'].'_y"').NBS.lang('yes', 'access_'.$row['group_id'].'_y').NBS.NBS.NBS.NBS.NBS.NBS.
						form_radio('access_'.$row['group_id'], 'n', $row['access_n'], 'id="access_'.$row['group_id'].'_n"').NBS.lang('no', 'access_'.$row['group_id'].'_n')
				 );
			}
			echo $this->table->generate();
		}
		?>


		<?php endif;?>

		<p><?=form_submit('category_edit', lang($submit_lang_key), 'class="submit"')?></p>
		

		<?=form_close()?>

</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file status_edit.php */
/* Location: ./themes/cp_themes/mobile/admin/status_edit.php */
			