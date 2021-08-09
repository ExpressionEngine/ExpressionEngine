<!doctype html>
<html>
	<head>
		<title>Error - ExpressionEngine</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" lang="en-us" dir="ltr">
		<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"  name="viewport">
		<style>
			<?php echo file_get_contents(__DIR__ . '/eecms-error.min.css'); ?>
		</style>
	</head>
	<body>
		<section class="wrap">
			<div class="err-wrap error">
				<?php if ($heading == 'Error'): ?>
					<p><b><?php echo $heading ?></b>: <?php echo $message; ?></p>
				<?php else: ?>
					<h1><?php echo $heading ?></h1>
					<h2><?php echo $message ?></h2>
				<?php endif ?>
			</div>
		</section>
		<script type="text/javascript" src="https://packettide.atlassian.net/s/d41d8cd98f00b204e9800998ecf8427e-T/-e6zu8v/b/23/a44af77267a987a660377e5c46e0fb64/_/download/batch/com.atlassian.jira.collector.plugin.jira-issue-collector-plugin:issuecollector/com.atlassian.jira.collector.plugin.jira-issue-collector-plugin:issuecollector.js?locale=en-US&collectorId=3804d578"></script>
	</body>
</html>
