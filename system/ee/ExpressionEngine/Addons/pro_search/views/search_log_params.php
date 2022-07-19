<ul>
<?php foreach ($params as $key => $val) : ?>
    <li><var><?=htmlspecialchars($key)?>=</var><?=htmlspecialchars($val)?></li>
<?php endforeach; ?>
</ul>
