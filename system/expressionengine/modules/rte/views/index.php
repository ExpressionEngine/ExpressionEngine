<div class="box mb">
	<?php $this->ee_view('_shared/form')?>
</div>
<div class="box snap">
	<div class="tbl-ctrls">
		<form>
			<fieldset class="tbl-search right">
				<a class="btn tn action" href="http://localhost/el-projects/ee-cp/views/addons-rte-new.php"><?=lang('create_new')?></a>
			</fieldset>
			<h1><?=lang('available_tool_sets')?></h1>
			<?php $this->ee_view('_shared/table', $table); ?>
			<?php $this->ee_view('_shared/pagination'); ?>
			<fieldset class="tbl-bulk-act">
				<select name="bulk_action">
					<option value="">-- <?=lang('with_selected')?> --</option>
					<option value="enable"><?=lang('enable')?></option>
					<option value="disable"><?=lang('disable')?></option>
					<option value="remove"><?=lang('remove')?></option>
				</select>
				<input class="btn submit" type="submit" value="<?=lang('submit')?>">
			</fieldset>
		</form>
	</div>
</div>