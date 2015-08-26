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
			<div class="toolbar-wrap">
				<ul class="toolbar">
					<li class="edit"><a href="<?=$mod['edit_url']?>" title="<?=strtolower(lang('edit_moderator'))?>"></a></li>
					<li class="remove"><a class="m-link" rel="modal-confirm-moderators" href="" title="<?=lang('remove_moderator')?>" data-confirm="<?=$mod['confirm']?>" data-id="<?=$mod['id']?>"></a></li>
				</ul>
			</div>
		</td>
	</tr>
	<?php endforeach; ?>
</table>
