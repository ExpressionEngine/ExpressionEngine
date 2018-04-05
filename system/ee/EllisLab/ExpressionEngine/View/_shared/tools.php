<?php
$classes[] = 'tools';

if (empty($toolbar))
{
//	var_dump(debug_backtrace());
}
if (count($toolbar->tools) > 3)
{
	$classes[] = 'tools--no-text';
}

switch ($toolbar->type)
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
	case 'sub_table':
		$classes[] = 'tools--no-bar';
		break;
	case 'icon_only':
		$classes[] = 'tools--no-text';
		break;
}
?>
<nav class="<?=implode(' ', array_unique($classes))?>">
	<?php foreach ($toolbar->tools as $tool):
		$attr = '';
		$class = 'tools__tool '.implode(' ', array_unique($tool->classes));
		foreach ($tool->attributes as $key => $val)
		{
			if ($key == 'class')
			{
				$class = $class . ' ' . $val;
				continue;
			}

			$attr .= ' ' . $key . '="' . $val . '"';
		} ?>
		<a href="<?=$tool->url?>" <?=$attr?> class="<?=$class?>">
			<span class="icon-tool icon-tool--<?=$tool->type?>"></span>
			<span class="tools__text"><?=$tool->title?></span>
		</a>
	<?php endforeach ?>
</nav>
