<!doctype html>
<html>
	<head>
		<title>Exception - ExpressionEngine</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" lang="en-us" dir="ltr">
		<meta content="width=device-width, initial-scale=1.0" name="viewport">
		<style>
			<?php echo file_get_contents(__DIR__.'/eecms-error.min.css'); ?>
		</style>
	</head>
	<body>
		<section class="wrap">
			<div class="err-wrap error" onclick="return err_toggle(this)">
				<h1>Exception Caught</h1>
				<h2><?php echo $message ?></h2>
				<p><?php echo $location ?></p>

				<?php if ($debug): ?>
					<h3>Stack Trace: <a class="toggle" rel="trace" href="#">show</a></h3>
					<div class="details trace">
						<ul>
							<?php foreach ($trace as $line): ?>
								<li><?php echo $line ?>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif ?>
			</div>
		</section>
	</body>

	<script>
	<?php echo file_get_contents(__DIR__.'/error_toggle.js') ?>
	</script>
</html>