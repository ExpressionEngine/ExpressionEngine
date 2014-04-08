<div class="col w-4">
	<div class="box sidebar">
		<?php
		// Grab the first and last items from the menu to determine
		// which items we need to put 'first' and 'last' classes on
		$first = array_values(array_slice($nav, 0, 1));
		$last = array_values(array_slice($nav, -1, 1));

		$i = 1;
		foreach ($nav as $key => $value):

			$class = '';
			$next = array_values(array_slice($nav, $i, 1));
			$i++;

			// If this the last item, OR this is an H2 but the next
			// item is an array, apply a class of 'last' to them both
			if (($value == $last[0]) OR
				(! is_array($value) && is_array($next) && $next == $last))
			{
				$class = 'last';
			}
			// Or if this is the first item, apply a class of 'first'
			elseif ($value == $first[0])
			{
				$class = 'first';
			}

			if ( ! is_array($value)): ?>
				<h2<?php if ( ! empty($class)):?> class="<?=$class?>"<?php endif ?>>
					<?php if (is_numeric($key)): ?>
						<?=lang($value)?>
					<?php else: ?>
						<a href="<?=$value?>"><?=lang($key)?></a>
					<?php endif; ?>
				</h2>
			<?php else: ?>
				<ul<?php if ( ! empty($class)):?> class="<?=$class?>"<?php endif ?>>
					<?php foreach ($value as $text => $link): ?>
						<li><a href="<?=$link?>"><?=lang($text)?></a></li>
					<?php endforeach ?>
				</ul>
			<?php endif;
		endforeach ?>
	</div>
</div>