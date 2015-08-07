<?php
	echo ee()->cp->render_footer_js();

	if (isset($library_src))
	{
		echo $library_src;
	}

	if (isset($script_foot))
	{
		echo $script_foot;
	}

	foreach (ee()->cp->footer_item as $item)
	{
		echo $item."\n";
	}
	?>
</body>
</html>