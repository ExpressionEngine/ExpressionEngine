<ul class="toolbar">
	<?php foreach ($toolbar_items as $type => $attributes):
		if (isset($attributes['type']))
		{
			$type = $attributes['type'];
		}
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
