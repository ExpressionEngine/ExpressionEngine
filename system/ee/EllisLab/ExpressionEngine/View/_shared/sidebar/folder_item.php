<li<?=$class?> data-<?=$key?>="<?=$value?>">
	<a href="<?=$url?>"<?php if ($external) echo ' rel="external"'?>><?=$text?></a>
	<?php
		if ($edit || $remove)
		{
			if ($edit)
			{
				$tools['edit'] = [
					'href' => $edit_url,
					'title' => lang('edit'),
				];
			}

			if ($remove)
			{
				$tools['remove'] = [
					'class' => 'm-link remove',
					'rel' => 'modal-confirm-'.$modal_name,
					'title' => lang('remove'),
					'data-confirm' => $confirm,
					'data-'.$key => $value,
				];
			}
		}

		echo $this->embed('_shared/tools', ['tools' => $tools, 'tool_type' => 'sidebar']);
	?>
</li>
