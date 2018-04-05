<table class="light">
	<?php
	$class = '';
	$last_mod = end($moderators);
	reset($moderators);
	foreach ($moderators as $mod):
		if ($last_mod == $mod)
		{
			$class = ' class="last"';
		}
	?>
	<tr<?=$class?>>
		<td><a href="<?=$mod['edit_url']?>" title="<?=strtolower(lang('edit_moderator'))?>"><?=$mod['name']?></a></td>
		<td>
			<?php
				$toolbar = ee('CP/Toolbar')->make('sub_table');
				$toolbar->addTool('edit', lang('edit_moderator'), $mod['edit_url']);
				$toolbar->addTool('remove', lang('remove_moderator'))
					->withData('confirm', $mod['confirm'])
					->withData('id', $mod['id'])
					->asModal('confirm-moderators')
					->asRemove();
				echo $toolbar->render();
			?>
		</td>
	</tr>
	<?php endforeach; ?>
</table>
