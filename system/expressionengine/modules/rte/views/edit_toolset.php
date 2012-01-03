	<?=form_open( $action )?>


	<div class="rte-toolset-builder ui-widget">
		<label for="rte-toolset-tools"><?=lang('toolset_builder_label')?></label><br/>
		<?=lang('toolset_builder_instructions')?><br/><br/>
		<input type="hidden" id="rte-toolset-tools" name="rte_selected_tools" value="<?=implode('|',$toolset_tools)?>"/>
		<table cellspacing="0" cellpadding="0" border="0" width="100%">
			<tr>
				<td class="rte-toolset-builder-pane" width="50%">
					<strong><?=lang('available_tools')?></strong>
					<div class="rte-toolset-builder-scrollpane" tabindex="0" style="overflow-x: hidden; overflow-y: auto; height: 200px; border:1px solid;">
						<ul id="rte-tools-unused" class="rte-tools-connected" style="height: 200px;">
<?php	foreach ( $unused_tools as $tool_id ): ?>
							<li class="rte-tool" data-tool-id="<?=$tool_id?>"><?=$available_tools[$tool_id]?></li>
<?php	endforeach; ?>
						</ul>
					</div>
				</td>

				<td class="rte-toolset-builder-buttons" width="40">
					<ul style="padding: 0 10px">
						<li class="ui-state-default ui-corner-all" style="margin: 5px 0">
							<a role="button" aria-disabled="true" class="ui-icon ui-icon-arrowthick-1-e" id="rte-tools-select" title="<?=lang('select_tool')?>"></a>
						</li>
						<li class="ui-state-default ui-corner-all">
							<a role="button" aria-disabled="true" class="ui-icon ui-icon-arrowthick-1-w" id="rte-tools-deselect" title="<?=lang('deselect_tool')?>"></a>
						</li>
					</ul>
				</td>

				<td class="rte-toolset-builder-pane" width="50%">
					<strong><?=lang('tools_in_toolset')?></strong>
					<div class="rte-toolset-builder-scrollpane" tabindex="0" style="overflow-x: hidden; overflow-y: auto; height: 200px; border:1px solid;">
						<ul id="rte-tools-selected" class="rte-tools-connected ui-sortable" style="height: 200px;">
<?php	foreach ( $toolset_tools as $tool_id ): ?>
							<li class="rte-tool" data-tool-id="<?=$tool_id?>"><?=$available_tools[$tool_id]?></li>
<?php	endforeach; ?>
						</ul>
					</div>
				</td>
			</tr>
		</table>
	</div>

	<p><?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'));?> or
	   <a href="<?=$module_base?>"><?=lang('cancel')?></a></p>
	
	<?=form_close();?>