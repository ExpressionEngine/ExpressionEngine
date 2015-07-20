<?php

// --------------------------------------------------------------------

/**
 * Required modules
 */

// 'channel', 'member', 'stats' are already required by the system
$required_modules = array("mailinglist", "rss", "comment", "search", "stats", "emoticon");

// --------------------------------------------------------------------

/**
 * Optional Values, Used for a Fresh Site
 */

$default_group = 'site';

// --------------------------------------------------------------------

/**
 * Default Preferences and Access Permissions for all Templates
 */

$default_template_preferences = array('caching'			=> 'n',
									  'cache_refresh'	=> 0,
									  'php_parsing'		=> 'none', // none, input, output
									  );

// Uses the Labels of the default four groups, as it is easier than the Group IDs, let's be honest
$default_template_access = array('Banned' 	=> 'n',
								 'Guests'	=> 'y',
								 'Pending'	=> 'y');

// --------------------------------------------------------------------

/**
 * Template Specific Preferences and Settings
 */

$template_preferences['site']['index'] = array('caching' => 'y', 'cache_refresh' => 60);

$template_access['search']['index'] = array('Guests' => 'n', 'Pending' => 'n');
				

/* End of file theme_preferences.php */
/* Location: ./themes/site_themes/beta_classic/theme_preferences.php */