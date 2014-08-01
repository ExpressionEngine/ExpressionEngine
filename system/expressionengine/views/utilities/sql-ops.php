<?php extend_template('default-nav'); ?>

<form class="tbl-ctrls">
	<fieldset class="tbl-search right">
		<input placeholder="<?=lang('type_phrase')?>" type="text" value="">
		<input class="btn submit" type="submit" value="<?=lang('search_table')?>">
	</fieldset>
	<h1><?=$cp_page_title?></h1>
	<div class="tbl-wrap">
		<table cellspacing="0">
			<tr>
				<th class="highlight"><?=lang('table')?> <a href="#" class="ico sort asc right"></a></th>
				<th><?=lang('status')?> <a href="#" class="ico sort desc right"></a></th>
				<th><?=lang('message')?> <a href="#" class="ico sort desc right"></a></th>
			</tr>
			<?php foreach ($results as $table => $result): ?>
				<tr>
					<td><?=$table?></td>
					<td><span class="st-<?=$result[2]?>">status</span></td>
					<td><?=$result[3]?></td>
				</tr>
			<?php endforeach ?>
		</table>
	</div>
</form>