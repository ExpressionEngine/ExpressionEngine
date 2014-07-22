<?php if (isset($wrap) && $wrap == TRUE): ?>
	<div class="tbl-wrap">
<?php endif ?>

<table cellspacing="0">
	<tr>
		<?php foreach ($data[0] as $key => $value): ?>
			<th class="highlight"><?=$key?> <a href="#" class="ico sort asc right"></a></th>
		<?php endforeach ?>
	</tr>
	<?php foreach ($data as $row): ?>
		<tr>
			<?php foreach ($row as $column): ?>
				<?php if (isset($encode) && $encode == TRUE): ?>
					<td><?=htmlspecialchars($column)?></td>
				<?php else: ?> 
					<td><?=$column?></td>
				<?php endif ?>
			<?php endforeach ?>
		</tr>
	<?php endforeach ?>
</table>

<?php if (isset($wrap) && $wrap == TRUE): ?>
	</div>
<?php endif ?>