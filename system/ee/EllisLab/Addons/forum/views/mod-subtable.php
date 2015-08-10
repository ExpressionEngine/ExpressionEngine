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
		<td><?=$mod['name']?></td>
		<td>
			<ul class="toolbar">
				<li class="edit"><a href="<?=$mod['edit_url']?>" title="<?=lang('edit_moderator')?>"></a></li>
				<li class="remove"><a href="" title="<?=lang('remove_moderator')?>"></a></li>
			</ul>
		</td>
	</tr>
	<?php endforeach; ?>
</table>
