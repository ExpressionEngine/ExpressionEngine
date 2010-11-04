<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=members" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>

	<?=form_open('C=members'.AMP.'M=new_member_form');?>
	<ul>
		<li>
			<?=form_error('username')?>
			<?=form_label(lang('username'), 'username')?><br />
			<?=form_input(array('id'=>'username', 'name'=>'username', 'value'=>set_value('username'), 'maxlength'=>50, 'placeholder' => lang('username')))?>
		</li>
		<li>
			<?=form_error('password')?>
			<?=form_label(lang('password'), 'password')?><br />
			<?=form_password(array('id'=>'password','name'=>'password','class'=>'long_field','value'=>set_value('password'), 'placeholder' => lang('password')))?>
		</li>
		<li>
			<?=form_error('password_confirm')?>
			<?=form_label(lang('password_confirm'), 'password_confirm')?><br />
			<?=form_password(array('id'=>'password_confirm','name'=>'password_confirm','value'=>set_value('password_confirm'), 'maxlength'=>40, 'placeholder' => lang('password_confirm')))?>
		</li>		
		<li>
			<?=form_error('screen_name')?>
			<?=form_label(lang('screen_name'), 'screen_name')?><br />
			<?=form_input(array('id'=>'screen_name','name'=>'screen_name','class'=>'long_field','value'=>set_value('screen_name'), 'maxlength'=>50, 'placeholder' => lang('screen_name')))?>
		</li>
		
		<li><?=form_error('email')?>
			<?=form_label(lang('email'), 'email')?><br />
			<?=form_input(array('id'=>'email','name'=>'email','class'=>'long_field','value'=>set_value('email'), 'maxlength'=>72, 'placeholder' => lang('email')))?>	
		</li>
		<?php if ($this->cp->allowed_group('can_admin_mbr_groups')):?>			
			<li>
				<?=form_error('group_id')?>
				<?=form_label(lang('member_group_assignment'), 'group_id')?><br />
				<?=form_dropdown('group_id', $member_groups, set_value('group_id', 5), 'id="group_id"')?>
			</li>
		<?php endif;?>	
		
		
		<li><?=form_label(lang('birthday'), 'bday_y')?><br />
			<?=form_dropdown('bday_y', $bday_y_options, $bday_y, 'id="bday_y"').' '.
				form_dropdown('bday_m', $bday_m_options, $bday_m, 'id="bday_m"').' '.
				form_dropdown('bday_d', $bday_d_options, $bday_d, 'id="bday_d"')?>	
		</li>

		<li><?=form_label(lang('url'), 'url')?><br />
				<?=form_input(array(
					'id'=>'url',
					'name'=>'url',
					'class'=>'field',
					'value'=>$url,
					'maxlength'=>150))?>	
		</li>

		<li><?=form_label(lang('location'), 'location')?><br />
				<?=form_input(array(
					'id'=>'location',
					'name'=>'location',
					'class'=>'field',
					'value'=>$location,
					'maxlength'=>50))?>	
		</li>

		<li><?=form_label(lang('occupation'), 'occupation')?><br />
				<?=form_input(array(
					'id'=>'occupation',
					'name'=>'occupation',
					'class'=>'field',
					'value'=>$occupation,
					'maxlength'=>80))?>	
		</li>

		<li><?=form_label(lang('interests'), 'interests')?><br />
				<?=form_input(array(
					'id'=>'interests',
					'name'=>'interests',
					'class'=>'field',
					'value'=>$interests,
					'maxlength'=>120))?>	
		</li>

		<li><?=form_label(lang('aol_im'), 'aol_im')?><br />
				<?=form_input(array(
					'id'=>'aol_im',
					'name'=>'aol_im',
					'class'=>'field',
					'value'=>$aol_im,
					'maxlength'=>50))?>	
		</li>

		<li><?=form_label(lang('icq'), 'icq')?><br />
				<?=form_input(array(
					'id'=>'icq',
					'name'=>'icq',
					'class'=>'field',
					'value'=>$icq,
					'maxlength'=>50))?>	
		</li>

		<li><?=form_label(lang('yahoo_im'), 'yahoo_im')?><br />
				<?=form_input(array(
					'id'=>'yahoo_im',
					'name'=>'yahoo_im',
					'class'=>'field',
					'value'=>$yahoo_im,
					'maxlength'=>50))?>	
		</li>

		<li><?=form_label(lang('msn_im'), 'msn_im')?><br />
				<?=form_input(array(
					'id'=>'msn_im',
					'name'=>'msn_im',
					'class'=>'field',
					'value'=>$msn_im,
					'maxlength'=>50))?>	
		</li>
		
		<li><?=form_label(lang('bio'), 'bio')?><br />
				<?=form_textarea(array(
					'id'=>'bio',
					'rows'=> 12,
					'name'=>'bio',
					'class'=>'field',
					'style'=>'width:99%;',  
					'value'=>$bio))?>	
		</li>	
		
			<?php
			// Custom Fields
			foreach($custom_profile_fields as $row)
			{
				$required  = ($row['m_field_required'] == 'n') ? '' : required();
				
				if ($row['m_field_type'] == 'textarea') // Textarea fieled types
				{
					$rows = ( ! isset($row['m_field_ta_rows'])) ? '10' : $row['m_field_ta_rows'];

					echo '<li>'.form_error('m_field_id_'.$row['m_field_id']);
					echo form_label($required.$row['m_field_label'], 'm_field_id_'.$row['m_field_id']).
						BR.$row['m_field_description']; 
					echo form_textarea(array(
							'name'	=>	'm_field_id_'.$row['m_field_id'],
							'class'	=>	'field',
							'id'	=>	'm_field_id_'.$row['m_field_id'], 
							'rows'	=>	$rows, 
							'style'=>'width:99%;', 
							'value'	=> 	set_value('m_field_id_'.$row['m_field_id'])));
					echo '</li>';
				}
				elseif ($row['m_field_type'] == 'select') // Drop-down lists
				{
					$dropdown_options = array();
					foreach (explode("\n", trim($row['m_field_list_items'])) as $v)
					{
						$v = trim($v);
						$dropdown_options[$v] = $v;
					}

					echo '<li>'.form_error('m_field_id_'.$row['m_field_id']);
					echo form_label($required.$row['m_field_label'], 'm_field_id_'.$row['m_field_id']).
						BR.$row['m_field_description'];
					echo form_dropdown('m_field_id_'.$row['m_field_id'], $dropdown_options, set_value('m_field_id_'.$row['m_field_id']), 'id="m_field_id_'.$row['m_field_id'].'"');
					echo '</li>';
				}
				elseif ($row['m_field_type'] == 'text') // Text input fields
				{
					echo '<li>'.form_error('m_field_id_'.$row['m_field_id']);
					echo form_label($required.$row['m_field_label'], 'm_field_id_'.$row['m_field_id']).
						BR.$row['m_field_description'];
					echo form_input(array(
							'name'		=>	'm_field_id_'.$row['m_field_id'], 
							'id'		=>	'm_field_id_'.$row['m_field_id'], 
							'class'		=>	'field', 
							'value'		=>	set_value('m_field_id_'.$row['m_field_id']), 
							'maxlength'	=>	$row['m_field_maxl']));
					echo '</li>';
				}
			}
			
		?>				
		
	</ul>
	<?=form_submit('members', lang('register_member'), 'class="whiteButton"')?>
	<?=form_close()?>
</div>
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}
/* End of file register.php */
/* Location: ./themes/cp_themes/mobile/members/register.php */