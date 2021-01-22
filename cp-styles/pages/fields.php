<?php $page_title = 'Fields';
include(dirname(__FILE__) . '/_wrapper-head.php'); ?>

<script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.15.10/highlight.min.js"></script>
<script>
	hljs.initHighlightingOnLoad();
</script>

<script src=" https://cdnjs.cloudflare.com/ajax/libs/showdown/1.9.0/showdown.min.js"></script>

<div class="secondary-sidebar-container">
	<!-- <div class="secondary-sidebar">
		<div class="sidebar">
			<a href="#dropdowns" class="sidebar__link">Dropdowns</a>
		</div>
	</div> -->

	<div class="container typography" id="markdown">

	</div>

</div>

<script>
	<?php
    $lines = file_get_contents('./fields.md');
    $js = json_encode($lines);
    ?>

	var text = <?php echo $js; ?>;
	var html = new showdown.Converter().makeHtml(text);

	document.getElementById('markdown').innerHTML = html;
</script>

<?php include(dirname(__FILE__) . '/_wrapper-footer.php'); ?>
