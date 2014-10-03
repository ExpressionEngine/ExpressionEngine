<?php
		echo form_open($action);

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

		<h3><?=lang('toolsets')?></h3>

<?php
		$this->table->set_heading(
			lang('id'),
			lang('name'),
			lang('status'),
			lang('delete')
		);

		foreach($toolsets as $toolset)
		{
			if ($toolset['enabled'] == 'y')
			{
				$active = '<strong>'.lang('enabled').'</strong>';
				$action = '<a href="'.$module_base.AMP.'method=toggle_toolset'.AMP.'toolset_id='.$toolset['toolset_id'].AMP.'enabled=n">'.lang('disable_button').'</a>';
			}
			else
			{
				$active = '<strong>'.lang('disabled').'</strong>';
				$action = '<a href="'.$module_base.AMP.'method=toggle_toolset'.AMP.'toolset_id='.$toolset['toolset_id'].AMP.'enabled=y">'.lang('enable_button').'</a>';
			}

			$this->table->add_row(
				array(
					'style' => 'width:4%',
					'data'	=> $toolset['toolset_id'],
				),
				array(
					'style' => 'width:32%',
					'data'	=> '<a class="edit_toolset" href="'.$module_base.AMP.'method=edit_toolset'.AMP.'toolset_id='.$toolset['toolset_id'].'">'.htmlentities($toolset['name'], ENT_QUOTES).'</a>'
				),
				array(
					'style' => 'width:32%',
					'data' => $active.NBS."({$action})"
				),
				array(
					'style'	=> 'width:32%',
					'data'	=> '<a href="'.$module_base.AMP.'method=delete_toolset'.AMP.'toolset_id='.$toolset['toolset_id'].'">'.lang('delete').'</a>'
				)
			);
		}

		echo $this->table->generate(); ?>

		<p><a id="create_toolset" class="edit_toolset submit" style="display: inline-block"href="<?=$new_toolset_link?>"><?=lang('create_new_toolset')?></a></p>

		<p><?=NBS?></p>

		<h3><?=lang('tools')?></h3>
<?php
		$this->table->set_heading(
			lang('name'),
			lang('status')
		);

		foreach($tools as $tool)
		{
			if ($tool['enabled'] == 'y')
			{
				$active = '<strong>'.lang('enabled').'</strong>';
				$action = '<a href="'.$module_base.AMP.'method=toggle_tool'.AMP.'tool_id='.$tool['tool_id'].AMP.'enabled=n">'.lang('disable_button').'</a>';
			}
			else
			{
				$active = '<strong>'.lang('disabled').'</strong>';
				$action = '<a href="'.$module_base.AMP.'method=toggle_tool'.AMP.'tool_id='.$tool['tool_id'].AMP.'enabled=y">'.lang('enable_button').'</a>';
			}

			$this->table->add_row(
				$tool['name'],
				array( 'style' => 'width:66%', 'data' => $active.NBS."({$action})" )
			);
		}

		echo $this->table->generate(); ?>
