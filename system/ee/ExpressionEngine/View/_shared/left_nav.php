<div class="box sidebar">
	<?php
    // Grab the first and last items from the menu to determine
    // which items we need to put 'first' and 'last' classes on
    $first = array_values(array_slice($nav, 0, 1));
    $last = array_values(array_slice($nav, -1, 1));

    $i = 1;
    foreach ($nav as $key => $value):

        $button = null;
        $class = 'sidebar__section-title ';
        $next = array_values(array_slice($nav, $i, 1));
        $i++;

        // Or if this is the first item, apply a class of 'first'
        if ($value == $first[0]) {
            $class .= 'first ';
        }

        // If this the last item, OR this is an H2 but the next
        // item is an array, apply a class of 'last' to them both
        if (($value == $last[0]) or
            (! is_array($value) && is_array($next) && $next == $last)) {
            $class .= 'last ';
        }

        if (! is_array($value) or (is_array($value) && ! is_numeric($key))): ?>
			<h2<?php if (! empty($class)):?> class="<?=trim($class)?>"<?php endif ?>>
				<?php if (is_numeric($key)): ?>
					<?=lang($value)?>
				<?php else: ?>
					<?php
                    if (is_array($value)) {
                        if (isset($value['button'])) {
                            $button = $value['button'];
                            unset($value['button']);
                        }

                        $attr = '';
                        foreach ($value as $name => $val) {
                            $attr .= ' ' . $name . '="' . $val . '"';
                        }
                    } else {
                        $attr = 'href="' . $value . '"';
                    } ?>
					<a <?=$attr?>><?=lang($key)?></a>
					<?php if (isset($button)): ?>
						<a class="button button--secondary button--small" href="<?=$button['href']?>"><?=lang($button['text'])?></a>
					<?php endif ?>
				<?php endif; ?>
			</h2>
		<?php else: ?>
			<div class="scroll-wrap">
				<div class="folder-list">
					<?php foreach ($value as $text => $link): ?>
					<?php if (is_array($link)): ?>
					<div class="sidebar__link sidebar__link--parent <?=$link['class']?>"
						<?php if (! empty($link['attrs'])): ?>
						<?php foreach ($link['attrs'] as $attr => $val): ?>
						 data-<?=$attr?>="<?=$val?>">
						<?php endforeach ?>
						<?php endif ?>
						<a href="<?=$link['href']?>">
							<?=lang($text)?>
						</a>
					</div>
					<?php else: ?>
					<div class="sidebar__link sidebar__link--parent">
						<a href="<?=$link?>">
							<?=lang($text)?>
						</a>
					</div>
					<?php endif ?>
					<?php endforeach ?>
				</div>
			</div>
		<?php endif;
    endforeach ?>
</div>
