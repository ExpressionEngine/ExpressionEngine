<!doctype html>
<html>
	<head>
		<title><?=$error_type?> - ExpressionEngine</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" lang="en-us" dir="ltr">
		<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"  name="viewport">
		<style>
			<?php echo file_get_contents(__DIR__ . '/eecms-error.min.css'); ?>
		</style>
	</head>
	<body>
		<section class="wrap">
			<div class="err-wrap error">
				<h1><?=$error_type?> Caught</h1>
				<h2><?php echo $message ?></h2>
				<p><?php echo $location ?></p>

				<?php if ($debug): ?>
					<h3>Stack Trace: <i>Please include when reporting this error</i></h3>
					<ul>
						<?php foreach ($trace as $line): ?>
							<li><?php echo $line ?>
						<?php endforeach; ?>
					</ul>
				<?php endif ?>
			</div>
		</section>
		<script type="text/javascript" src="https://packettide.atlassian.net/s/d41d8cd98f00b204e9800998ecf8427e-T/-e6zu8v/b/23/a44af77267a987a660377e5c46e0fb64/_/download/batch/com.atlassian.jira.collector.plugin.jira-issue-collector-plugin:issuecollector/com.atlassian.jira.collector.plugin.jira-issue-collector-plugin:issuecollector.js?locale=en-US&collectorId=3804d578"></script>
	</body>
</html>
