<table class="grid_field" cellspacing="0" cellpadding="0">
	<thead>
		<?php foreach ($columns as $index => $column): ?>
			<th<?php if ($index == 0): ?> class="first" <?php endif ?>>
				<b><?=$column['col_label']?></b>
				<?php if ( ! empty($column['col_instructions'])): ?>
					<span class="instruction_text">
						<b>Instructions:</b> <?=$column['col_instructions']?>
					</span>
				<?php endif ?>
			</th>
		<?php endforeach ?>
	</thead>
	<tbody>
		<tr>
			<?php foreach ($columns as $index => $column): ?>
				<td<?php if ($index == 0): ?> class="first" <?php endif ?>>
					<?=$column['display_field']?>
				</td>
			<?php endforeach ?>
		</tr>
	</tbody>
</table>

<a class="grid_button_add" href="#">Add Row</a>

<!-- <img src="https://dl.dropbox.com/u/28047/4e593425cda77.gif"> -->