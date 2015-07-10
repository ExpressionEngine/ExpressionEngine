<ul class="toolbar">
	<?php foreach ($toolbar_items as $type => $attributes):
		$attr = '';
		$content = '';
		foreach ($attributes as $key => $val)
		{
			if ($key == 'content')
			{
				$content = $val;
				continue;
			}
			$attr .= ' ' . $key . '="' . $val . '"';
		} ?>
		<li class="<?=$type?>"><a <?=$attr?>><?=$content?></a></li>
	<?php endforeach ?>
</ul>