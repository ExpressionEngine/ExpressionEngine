<style>
/*
	_color.less
	----------
	part for defining color variables
	----------
		transparency
		gray-scale
		warm colors
		color colors
*/
/*
	_fx.less
	----------
	part for commonly used CSS "effects"
	----------
		.shadow (box-shadow)
		.txt-shadow (txt-shadow)
		.boxsize (box-sizing)
		.corners (border-radius)
		.grade (linear-gradient)
		.diagonal (linear-gradient, specifically for creating a repeating diagonal background)
		.fadend (linear-gradient, specifically for creating faded out ends on borders and separations)
		.ts (transition)
		.tf (transform)
*/
#expressionengine_profiler,
#expressionengine_template_debug {
  background-color: #f9f9f9;
  border-top: 1px solid #b5b5b5;
  font-family: 'HelveticaNeue-Light', 'Helvetica Neue Light', 'Helvetica Neue', 'Helvetica', 'Arial', Sans-Serif;
  font-size: 14px;
  padding: 10px 30px;
}
#expressionengine_profiler .inner-wrap,
#expressionengine_template_debug .inner-wrap {
  margin: 0 auto;
  max-width: 1200px;
}
#expressionengine_profiler table,
#expressionengine_template_debug table {
  background-color: #f9f9f9;
  border: 1px solid #b5b5b5;
  border-collapse: separate;
  border-spacing: 0;
  margin: 0;
  padding: 1px;
  width: 100%;
  -moz-box-sizing: border-box;
  -webkit-box-sizing: border-box;
  box-sizing: border-box;
}
#expressionengine_profiler table tr:last-child td,
#expressionengine_template_debug table tr:last-child td {
  border-bottom: 0;
}
#expressionengine_profiler table tbody tr:nth-child(2n),
#expressionengine_template_debug table tbody tr:nth-child(2n) {
  background-color: #ffffff;
}
#expressionengine_profiler table th,
#expressionengine_template_debug table th,
#expressionengine_profiler table td,
#expressionengine_template_debug table td {
  border-right: 1px solid #b5b5b5;
  font-family: 'HelveticaNeue-Light', 'Helvetica Neue Light', 'Helvetica Neue', 'Helvetica', 'Arial', Sans-Serif;
  padding: 10px;
}
#expressionengine_profiler table th:last-child,
#expressionengine_template_debug table th:last-child,
#expressionengine_profiler table td:last-child,
#expressionengine_template_debug table td:last-child {
  border-right: 0;
}
#expressionengine_profiler table th,
#expressionengine_template_debug table th {
  background-color: #3366cc;
  color: #ffffff;
}
#expressionengine_profiler table td,
#expressionengine_template_debug table td {
  border-bottom: 1px solid #b5b5b5;
  border-right-color: #b5b5b5;
}
/*
$output .= '<fieldset style="border:1px solid #0000FF;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee">';
			$output .= "\n";
			$output .= '<legend style="color:#0000FF;">&nbsp;&nbsp;'.$this->CI->lang->line('profiler_database').':&nbsp; '.$db->database.'&nbsp;&nbsp;&nbsp;'.$this->CI->lang->line('profiler_queries').': '.count($db->queries).'&nbsp;&nbsp;'.$show_hide_js.'</legend>';
			$output .= "\n";
			$output .= "\n\n<table style='width:100%;{$hide_queries}' id='ci_profiler_queries_db_{$count}'>\n";
*/
#expressionengine_profiler legend {
  background-color: #b5b5b5;
  font-family: 'HelveticaNeue-Light', 'Helvetica Neue Light', 'Helvetica Neue', 'Helvetica', 'Arial', Sans-Serif;
  font-weight: bold;
  margin-left: -11px;
  padding: 5px 10px;
}
#expressionengine_profiler legend span {
  font-weight: normal;
  padding: 6px 5px 5px;
}
#expressionengine_profiler fieldset {
  background-color: #ffffff;
  border: 1px solid #b5b5b5;
  font-family: 'HelveticaNeue-Light', 'Helvetica Neue Light', 'Helvetica Neue', 'Helvetica', 'Arial', Sans-Serif;
  margin: 20px 0;
  padding: 6px 10px 10px 10px;
}
#expressionengine_profiler #expressionengine_profiler_benchmark {
  border-color: #c43737;
}
#expressionengine_profiler #expressionengine_profiler_benchmark legend {
  background-color: #dd8484;
  color: #882626;
}
#expressionengine_profiler #expressionengine_profiler_get {
  border-color: #d67813;
}
#expressionengine_profiler #expressionengine_profiler_get legend {
  background-color: #f1aa5e;
  color: #90510d;
}
#expressionengine_profiler #expressionengine_profiler_memory {
  border-color: #ffae00;
}
#expressionengine_profiler #expressionengine_profiler_memory legend {
  background-color: #ffce66;
  color: #b37a00;
}
#expressionengine_profiler #expressionengine_profiler_post {
  border-color: #e8d554;
}
#expressionengine_profiler #expressionengine_profiler_post legend {
  background-color: #f4ebae;
  color: #a69416;
}
#expressionengine_profiler #expressionengine_profiler_database,
#expressionengine_profiler #expressionengine_profiler_duplicate_queries {
  border-color: #3382c5;
}
#expressionengine_profiler #expressionengine_profiler_database legend,
#expressionengine_profiler #expressionengine_profiler_duplicate_queries legend {
  background-color: #a8cbe9;
  color: #235a88;
}
#expressionengine_profiler #expressionengine_profiler_database legend span,
#expressionengine_profiler #expressionengine_profiler_duplicate_queries legend span {
  background-color: #29679c;
  color: #94bfe3;
}
#expressionengine_profiler #expressionengine_profiler_database legend span:hover,
#expressionengine_profiler #expressionengine_profiler_duplicate_queries legend span:hover {
  background-color: #2e75b1;
  color: #ffffff;
}
#expressionengine_profiler #expressionengine_profiler_server {
  border-color: #25cbbd;
}
#expressionengine_profiler #expressionengine_profiler_server legend {
  background-color: #71e5db;
  color: #198a81;
}
#expressionengine_profiler #expressionengine_profiler_server legend span {
  background-color: #1da095;
  color: #86e9e1;
}
#expressionengine_profiler #expressionengine_profiler_server legend span:hover {
  background-color: #21b5a9;
  color: #ffffff;
}
#expressionengine_profiler #expressionengine_profiler_server_data {
  /*display: none;*/
}
#expressionengine_profiler #expressionengine_profiler_userdata {
  border-color: #6f25fb;
}
#expressionengine_profiler #expressionengine_profiler_userdata legend {
  background-color: #b189fd;
  color: #4a04d0;
}
#expressionengine_profiler #expressionengine_profiler_userdata legend span {
  background-color: #5304e9;
  color: #c2a2fd;
}
#expressionengine_profiler #expressionengine_profiler_userdata legend span:hover {
  background-color: #5e0cfb;
  color: #ffffff;
}
#expressionengine_profiler #expressionengine_profiler_userdata_data {
  /*display: none;*/
}
#expressionengine_template_debug {
  background-color: #f1f1f1;
}
#expressionengine_template_debug h1 {
  background-color: #3366cc;
  border-bottom: 1px solid #b5b5b5;
  color: #f9f9f9;
  font-family: 'HelveticaNeue-Light', 'Helvetica Neue Light', 'Helvetica Neue', 'Helvetica', 'Arial', Sans-Serif;
  font-size: 16px;
  font-weight: normal;
  margin: 0;
  padding: 10px;
}
#expressionengine_template_debug .inner-wrap {
  border: 1px solid #b5b5b5;
  margin: 20px auto;
}
#expressionengine_template_debug div:not([class='inner-wrap']) {
  border-bottom: 1px solid #b5b5b5;
  font-size: 12px;
  padding: 20px;
}
#expressionengine_template_debug div:not([class='inner-wrap']):nth-child(2n) {
  background-color: #f9f9f9;
}
#expressionengine_template_debug div:not([class='inner-wrap']):last-child {
  border-bottom: 0;
}
</style>

<div id='expressionengine_profiler'>
	<?php
	foreach ($sections as $section)
	{
		echo $section;
	}
	?>
</div>
