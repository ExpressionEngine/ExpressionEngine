<ul>
<?php foreach ($params as $key => $val) : ?>
    <li><var><?=htmlspecialchars((string) $key)?>=</var><?=htmlspecialchars((string) $val)?></li>
<?php endforeach; ?>
</ul>
