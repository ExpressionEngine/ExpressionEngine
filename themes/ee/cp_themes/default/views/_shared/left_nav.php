<div class="col w-4 last">
	<div class="box sidebar">
		<?php
		// Grab the first and last items from the menu to determine
		// which items we need to put 'first' and 'last' classes on
		$first = array_values(array_slice($nav, 0, 1));
		$last = array_values(array_slice($nav, -1, 1));

		$i = 1;
		foreach ($nav as $key => $value):

			$button = NULL;
			$class = '';
			$next = array_values(array_slice($nav, $i, 1));
			$i++;

			// Or if this is the first item, apply a class of 'first'
			if ($value == $first[0])
			{
				$class .= 'first ';
			}

			// If this the last item, OR this is an H2 but the next
			// item is an array, apply a class of 'last' to them both
			if (($value == $last[0]) OR
				(! is_array($value) && is_array($next) && $next == $last))
			{
				$class .= 'last ';
			}

			if ( ! is_array($value) OR (is_array($value) && ! is_numeric($key))): ?>
				<h2<?php if ( ! empty($class)):?> class="<?=trim($class)?>"<?php endif ?>>
					<?php if (is_numeric($key)): ?>
						<?=lang($value)?>
					<?php else: ?>
						<?php
						if (is_array($value))
						{
							if (isset($value['button']))
							{
								$button = $value['button'];
								unset($value['button']);
							}

							$attr = '';
							foreach ($value as $name => $val)
							{
								$attr .= ' ' . $name . '="' . $val . '"';
							}
						}
						else
						{
							$attr = 'href="'.$value.'"';
						} ?>
						<a <?=$attr?>><?=lang($key)?></a>
						<?php if (isset($button)): ?>
							<a class="btn action" href="<?=$button['href']?>"><?=lang($button['text'])?></a>
						<?php endif ?>
					<?php endif; ?>
				</h2>
			<?php else: ?>
				<ul<?php if ( ! empty($class)):?> class="<?=$class?>"<?php endif ?>>
					<?php foreach ($value as $text => $link): ?>
						<?php if(is_array($link)): ?>
						<li class="<?=$link['class']?>">
							<a
								<?php if ( ! empty($link['attrs'])): ?>
									<?php foreach ($link['attrs'] as $attr => $val): ?>
									<?=$attr?>="<?=$val?>"
									<?php endforeach ?>
								<?php endif ?>
								href="<?=$link['href']?>"><?=lang($text)?></a>
						</li>
						<?php else: ?>
						<li><a href="<?=$link?>"><?=lang($text)?></a></li>
						<?php endif ?>
					<?php endforeach ?>
				</ul>
			<?php endif;
		endforeach ?>
	</div>
</div>
