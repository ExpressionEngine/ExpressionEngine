<nav class="tools <?=(count($tools) > 3) ? 'tools--no-text' : ''?>">
	<?php foreach ($tools as $type => $attributes):
		if (isset($attributes['type']))
		{
			$type = $attributes['type'];
		}
		$attr = '';
		$text = '';
		$class = 'tools__tool';
		foreach ($attributes as $key => $val)
		{
			if ($key == 'content' OR $key == 'title')
			{
				$text = $val;
				continue;
			}

			if ($key == 'class')
			{
				$class = $class . ' ' . $val;
				continue;
			}

			$attr .= ' ' . $key . '="' . $val . '"';
		} ?>
		<a <?=$attr?> class="<?=$class?>">
			<span class="icon-tool icon-tool--<?=$type?>"></span>
			<span class="tools__text"><?=$text?></span>
		</a>
	<?php endforeach ?>
</nav>
