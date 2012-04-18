	<?=form_open($action)?>

<?php	$this->table->set_template($cp_pad_table_template);
		$this->table->template['thead_open'] = '<thead class="visualEscapism">';
		$this->table->set_caption(lang('rte_settings'));
		$this->table->set_heading(
			lang('preference'),
			lang('setting')
		);
		$this->table->add_row(
			'<strong>'.lang('enable_rte_globally').'</strong>',
			array(
				'style'	=> 'width:50%',
				'data'	=> lang('yes','rte_enabled_y').NBS.
					form_radio(array('name'=>'rte_enabled','id'=> 'rte_enabled_y','value'=>'y','checked'=>($rte_enabled == 'y'))).
					NBS.NBS.NBS.NBS.NBS.
					lang('no', 'rte_enabled_n').NBS.
					form_radio(array('name'=>'rte_enabled','id'=>'rte_enabled_n','value'=>'n','checked'=>($rte_enabled == 'n'))).
					NBS.NBS.NBS.NBS.NBS
			)
		);
		$this->table->add_row(
			'<strong>'.lang('default_toolset').'</strong>'.BR.lang('default_toolset_details'),
			array(
				'style'	=> 'width:50%',
				'data'	=> form_dropdown('rte_default_toolset_id', $toolset_opts, $rte_default_toolset_id)
			)
		);
		echo $this->table->generate(); ?>

		<p><?=form_submit(array('name'=>'submit','value'=>lang('update'),'class'=>'submit'));?></p>
		<?=form_close();?>
		<p><?=NBS?></p>
		
<?php	$this->table->clear();
		$this->table->set_template($cp_pad_table_template);
		$this->table->template['thead_open'] = '<thead class="visualEscapism">';
		$this->table->set_caption(lang('rte_toolsets'));
		$this->table->set_heading(lang('toolset'), lang('status'), '');

		foreach($toolsets as $toolset)
		{
			if ($toolset['enabled'] == 'y')
			{
				$active = '<strong>'.lang('enabled').'</strong>';
				$action = '<a href="'.$module_base.AMP.'method=toggle_toolset'.AMP.'rte_toolset_id='.$toolset['rte_toolset_id'].AMP.'enabled=n">'.lang('disable_button').'</a>';
			}
			else
			{
				$active = '<strong>'.lang('disabled').'</strong>';
				$action = '<a href="'.$module_base.AMP.'method=toggle_toolset'.AMP.'rte_toolset_id='.$toolset['rte_toolset_id'].AMP.'enabled=y">'.lang('enable_button').'</a>';
			}
	
			$this->table->add_row(
				array(
					'style' => 'width:34%',
					'data'	=> '<a class="edit_toolset" href="'.$module_base.AMP.'method=edit_toolset'.AMP.'rte_toolset_id='.$toolset['rte_toolset_id'].'">'.$toolset['name'].'</a>'
				),
				array(
					'style' => 'width:33%',
					'data' => $active.NBS."({$action})"
				),
				array(
					'style'	=> 'width:33%',
					'data'	=> '<a href="'.$module_base.AMP.'method=delete_toolset'.AMP.'rte_toolset_id='.$toolset['rte_toolset_id'].'">'.lang('delete').'</a>'
				)
			);
		}

		echo $this->table->generate(); ?>
		
		<p><a id="create_toolset" class="edit_toolset submit" style="display: inline-block"href="<?=$new_toolset_link?>"><?=lang('create_new_rte_toolset')?></a></p>
		
		<p><?=NBS?></p>

<?php	$this->table->clear();
		$this->table->set_template($cp_pad_table_template);
		$this->table->template['thead_open'] = '<thead class="visualEscapism">';
		$this->table->set_caption(lang('rte_tools'));
		$this->table->set_heading(
			lang('tool'),
			lang('status')
		);

		foreach($tools as $tool)
		{
			if ($tool['enabled'] == 'y')
			{
				$active = '<strong>'.lang('enabled').'</strong>';
				$action = '<a href="'.$module_base.AMP.'method=toggle_tool'.AMP.'rte_tool_id='.$tool['rte_tool_id'].AMP.'enabled=n">'.lang('disable_button').'</a>';
			}
			else
			{
				$active = '<strong>'.lang('disabled').'</strong>';
				$action = '<a href="'.$module_base.AMP.'method=toggle_tool'.AMP.'rte_tool_id='.$tool['rte_tool_id'].AMP.'enabled=y">'.lang('enable_button').'</a>';
			}
	
			$this->table->add_row(
				$tool['name'],
				array( 'style' => 'width:66%', 'data' => $active.NBS."({$action})" )
			);
		}

		echo $this->table->generate(); ?>