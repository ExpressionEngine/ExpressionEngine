<?php
$classes[] = 'tools';

if (count($tools) > 3)
{
	$classes[] = 'tools-no-text';
}

if (isset($type))
{
	switch ($type)
	{
		case 'list':
			$classes[] = 'tools--tbl-list';
			break;
		case 'sidebar':
			$classes[] = 'tools--no-text';
			$classes[] = 'tools--in-list';
			break;
		case 'select':
			$classes[] = 'tools--no-bar';
			$classes[] = 'tools--align-right';
			break;
		case 'html_buttons':
			$classes[] = 'tools--bar';
			$classes[] = 'tools--html';
			$classes[] = 'tools--before';
			break;
		case 'rte':
			$classes[] = 'tools--bar';
			$classes[] = 'tools--rte';
			break;
		case 'log':
			$classes[] = 'tools--in-logs';
			$classes[] = 'tools--no-bar';
			break;
		case 'sub_header':
			$classes[] = 'tools--in-head';
			break;
	}
}
?>
<nav class="<?=implode(' ', array_unique($classes))?>">
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
