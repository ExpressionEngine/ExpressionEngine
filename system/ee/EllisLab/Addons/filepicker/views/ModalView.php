<div class="tbl-ctrls">
<?=form_open($table['base_url'])?>
	<?php if (is_numeric($dir)): ?>
	<fieldset class="tbl-search right">
		<a class="btn tn action" href="<?=ee('CP/URL', "files/upload/$dir")?>">Upload New File</a>
	</fieldset>
	<?php endif ?>
	<h1>
		<?php if (is_numeric($dir)): ?>
		<ul class="toolbar">
			<li class="sync">
				<a href="<?=ee('CP/URL', "settings/upload/sync/$dir")?>" title="<?=lang('sync_directories')?>"></a>
			</li>
		</ul>
		<?php endif ?>
		<?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?>
	</h1>

	<?=ee('Alert')->getAllInlines()?>

	<?php if (isset($filters)) echo $filters; ?>

	<?php if($images): ?>
		<table class='img-grid'>
		<?php foreach (array_chunk($table['data'], 4) as $row): ?>
			<tr>
				<?php foreach ($row as $column): ?>
				<td><a data-id="<?=$column['attrs']['data-id']?>" class="filepicker-item" href="#"><img src="<?=$data[$column['attrs']['data-id']]?>" alt="avatar"></a></td>
				<?php endforeach ?>
			</tr>
		<?php endforeach ?>
		</table>
	<?php else: ?>
		<?php $this->view('ee:_shared/table', $table); ?>
	<?php endif; ?>

	<?php if ( ! empty($pagination)) echo $pagination; ?>

<?=form_close()?>
</div>
