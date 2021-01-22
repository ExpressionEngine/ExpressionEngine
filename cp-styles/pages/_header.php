<?php
    // Current page filename
    $current_page = basename($_SERVER["SCRIPT_FILENAME"], '.php');

    // Title of the page
    $page_title = $page_title ?? 'Page';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php if ($page_title != '') {
    echo "$page_title | ";
}?>ExpressionEngine</title>

    <link rel="stylesheet" type="text/css" media="screen" href="../../themes/ee/cp/css/common.min.css" />

    <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
</head>
<body data-theme="light">
<script>
	var currentTheme = localStorage.getItem('theme');

	if (currentTheme) {
		document.body.dataset.theme = currentTheme
	}
</script>
