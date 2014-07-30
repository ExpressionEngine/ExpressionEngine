<?php extend_template('default-nav', 'outer_box'); ?>

<div class="box mb">
	<h1><?=lang('sql_manager_abbr')?></h1>
	<div class="txt-wrap">
		<ul class="checklist">
			<li><?=lang('mysql')?> <?=$sql_version?> / <b><?=lang('total_records')?>:</b> <?=$records?> / <b><?=lang('size')?>: </b><?=$size?></li>
			<li class="last"><b><?=lang('uptime')?>:</b> <?=$database_uptime?></li>
		</ul>
	</div>
</div>
<div class="box">
	<?=form_open(cp_url('utilities/sql'), 'class="tbl-ctrls"')?>
	<?php $this->view('_shared/form_messages')?>
		<fieldset class="tbl-search right">
			<input placeholder="<?=lang('type_phrase')?>" type="text" value="">
			<input class="btn submit" type="submit" value="<?=lang('search_tables')?>">
		</fieldset>
		<h1><?=lang('database_tables')?></h1>
		<div class="tbl-wrap">
			<table cellspacing="0">
				<tr>
					<th class="highlight"><?=lang('table_name')?> <a href="#" class="ico sort asc right"></a></th>
					<th><?=lang('records')?> <a href="#" class="ico sort desc right"></a></th>
					<th><?=lang('size')?> <a href="#" class="ico sort desc right"></a></th>
					<th><?=lang('manage')?></th>
					<th class="check-ctrl"><input type="checkbox" title="select all"></th>
				</tr>
				<?php foreach ($status as $table): ?>
					<tr>
						<td><?=$table['name']?></td>
						<td><?=$table['rows']?></td>
						<td><?=$table['size']?></td>
						<td>
							<ul class="toolbar">
								<li class="view"><a href="<?=cp_url('utilities/query/run-query', array('thequery' => rawurlencode(base64_encode('SELECT * FROM '.$table['name']))))?>" title="view"></a></li>
							</ul>
						</td>
						<td><input name="table[]" value="<?=$table['name']?>" type="checkbox"></td>
					</tr>
				<?php endforeach ?>
			</table>
		</div>
		<fieldset class="tbl-bulk-act">
			<select name="table_action">
				<option value="none">-- with selected --</option>
				<option value="REPAIR">Repair</option>
				<option value="OPTIMIZE">Optimize</option>
			</select>
			<input class="btn submit" type="submit" value="submit">
		</fieldset>
	</form>
</div>