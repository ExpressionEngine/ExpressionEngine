<li<?=$class?> data-<?=$key?>="<?=$value?>">
	<a href="<?=$url?>"<?php if ($external) echo ' rel="external"'?>><?=$text?></a>
	<?php
		$toolbar = ee('CP/Toolbar')->make('sidebar');

		if ($edit)
		{
			$toolbar->addTool('edit', lang('edit'), $edit_url);
		}

		if ($remove)
		{
			$toolbar->addTool('remove', lang('remove'))
				->withData('confirm', $confirm)
				->withData('key', $value)
				->asModal('confirm-'.$modal_name)
				->asRemove();
		}

		echo $toolbar->render();
	?>
</li>
