<?php
	$class = strtolower($error_category);
	$class = ($class == 'warning') ? 'warn' : $class;
?>

<script>
<?php echo file_get_contents(__DIR__.'/error_toggle.js') ?>
</script>

<div class="err-wrap <?php echo $class ?>" onclick="return err_toggle(this)">
	<h1><?php echo $error_category ?></h1>
	<h2><?php echo $message ?></h2>
	<p><?php echo $filepath ?>, line <?php echo $line ?> <a class="toggle" rel="notice-info" href="#">show details</a></p>
	<div class="details <?php echo $class ?>-info">
		<ul>
			<li><b>Severity</b>: <?php echo $error_constant ?></li>
		</ul>
	</div>
</div>