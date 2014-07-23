<?php if (isset($wrap) && $wrap == TRUE): ?>
	<div class="tbl-wrap">
<?php endif ?>

<table cellspacing="0">
	<tr>
		<?php foreach ($data[0] as $key => $value): ?>
			<th<?php if (isset($sort) && $sort == $key): ?> class="highlight"<?php endif ?>>
				<?=$key?>
				<?php if (isset($sort)):
					$arrow_dir = ($sort == $key) ? $sort_dir : 'desc';
					$link_dir = ($arrow_dir == 'asc') ? 'desc' : 'asc';
					$base_url->setQueryStringVariable('sort', $key);
					$base_url->setQueryStringVariable('sort_dir', $link_dir);
				?>
					<a href="<?=$base_url?>" class="ico sort <?=$arrow_dir?> right"></a>
				<?php endif ?>
			</th>
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