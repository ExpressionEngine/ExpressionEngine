<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed.');

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * ExpressionEngine HTML Glossary for Control Panel Publish Page
 */

// The left side of the array is the text that appears in the link
// The right side is the tag or entity to be inserted

ee()->load->library('logger');
ee()->logger->developer('The glossary config file has been deprecated and will be removed.', TRUE, 604800);

$glossary[1][] = array('anchor',			"&lt;a href=''&gt;&lt;/a&gt;");
$glossary[1][] = array('image_link',		"&lt;img src='' /&gt;");
$glossary[1][] = array('blockquote',		'&lt;blockquote&gt;&lt;/blockquote&gt;');
$glossary[1][] = array('bold',				'&lt;strong&gt;&lt;/strong&gt;');
$glossary[1][] = array('italic',			'&lt;em&gt;&lt;/em&gt;');
$glossary[1][] = array('heading_1',			'&lt;h1&gt;&lt;/h1&gt;');
$glossary[1][] = array('heading_2',			'&lt;h2&gt;&lt;/h2&gt;');
$glossary[1][] = array('heading_3',			'&lt;h3&gt;&lt;/h3&gt;');
$glossary[1][] = array('heading_4',			'&lt;h4&gt;&lt;/h4&gt;');
$glossary[1][] = array('heading_5',			'&lt;h5&gt;&lt;/h5&gt;');


$glossary[2][] = array('paragraph',			'&lt;p&gt;&lt;/p&gt;');
$glossary[2][] = array('pre',				'&lt;pre&gt;&lt;/pre&gt;');
$glossary[2][] = array('div',				'&lt;div&gt;&lt;/div&gt;');
$glossary[2][] = array('span',				'&lt;span&gt;&lt;/span&gt;');
$glossary[2][] = array('nonbr_space',		'&nbsp;');
$glossary[2][] = array('line_break',		'&lt;br /&gt;');
$glossary[2][] = array('horizontal_rule',	'&lt;hr /&gt;');
$glossary[2][] = array('font',	"&lt;style='font-family: Verdana; font-size:11px; color:#000;'&gt;");
$glossary[2][] = array('unordered_list',	"&lt;ul&gt;\n&lt;li&gt;&lt;/li&gt;\n&lt;/ul&gt;");
$glossary[2][] = array('ordered_list',		"&lt;ol&gt;\n&lt;li&gt;&lt;/li&gt;\n&lt;/ol&gt;");


$glossary[3][] = array('&lt;  &amp;lt;',			'&lt;');
$glossary[3][] = array('&gt;  &amp;gt;',			'&gt;');
$glossary[3][] = array('&amp;  &amp;amp;',			'&amp;');
$glossary[3][] = array('&laquo;  &amp;laquo;',		'&laquo;');
$glossary[3][] = array('&raquo;  &amp;raquo;',		'&raquo;');
$glossary[3][] = array('&lsaquo;  &amp;lsaquo;',	'&lsaquo;');
$glossary[3][] = array('&rsaquo;  &amp;rsaquo;',	'&rsaquo;');
$glossary[3][] = array('&copy;  &amp;copy;',		'&copy;');
$glossary[3][] = array('&trade;  &amp;trade;',		'&trade;');


$glossary[4][] = array('&ndash;  &amp;ndash;',		'&ndash;');
$glossary[4][] = array('&mdash;  &amp;mdash;',		'&mdash;');
$glossary[4][] = array('&hellip;  &amp;hellip;',	'&hellip;');
$glossary[4][] = array('&ldquo;  &amp;ldquo;',		'&ldquo;');
$glossary[4][] = array('&rdquo;  &amp;rdquo;',		'&rdquo;');
$glossary[4][] = array('&lsquo;  &amp;lsquo;',		'&lsquo;');
$glossary[4][] = array('&rsquo;  &amp;rsquo;',		'&rsquo;');
$glossary[4][] = array('&bull;  &amp;bull;',		'&bull;');
$glossary[4][] = array('&middot;  &amp;middot;',	'&middot;');

// EOF
