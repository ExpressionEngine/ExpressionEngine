<?php
    $class = strtolower($error_category);
    $class = ($class == 'warning') ? 'warn' : $class;
    $class = ($class == 'deprecated') ? 'deprecate' : $class;
?>

<div class="err-wrap <?php echo $class ?>">
	<h1><?php echo $error_category ?></h1>
	<h2><?php echo $message ?></h2>
	<p><?php echo $filepath ?>, line <?php echo $line ?></p>
	<ul>
		<li><b>Severity</b>: <?php echo $error_constant ?></li>
	</ul>
</div>
