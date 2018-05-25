<?php

return [
	'author'         => 'EllisLab',
	'author_url'     => 'https://ellislab.com/',
	'name'           => 'Consent',
	'description'    => 'Front end Consent management',
	'version'        => '1.0.0',
	'namespace'      => 'EllisLab\Addons\Consent',
	'settings_exist' => FALSE,
	'built_in'       => TRUE,

	// Consent Requests
	'consent.requests' => [
	  'cookies_functionality' => [
	    'title' => 'Functionality Cookies',
	    'request' => 'These cookies help us personalize content and functionality for you, including remembering changes you have made to parts of the website that you can customize, or selections for services made on previous visits. If you do not allow these cookies, some portions of our website may be less friendly and easy to use, forcing you to enter content or set your preferences on each visit.',
	    'request_format' => 'none',
	  ],
	  'cookies_performance' => [
	    'title' => 'Performance Cookies',
	    'request' => 'These cookies allow us measure how visitors use our website, which pages are popular, and what our traffic sources are. This helps us improve how our website works and make it easier for all visitors to find what they are looking for. The information is aggregated and anonymous, and cannot be used to identify you. If you do not allow these cookies, we will be unable to use your visits to our website to help make improvements.',
	    'request_format' => 'none',
	  ],
	  'cookies_targeting' => [
	    'title' => 'Targeting Cookies',
	    'request' => 'These cookies are usually placed by third-party advertising networks, which may use information about your website visits to develop a profile of your interests. This information may be shared with other advertisers and/or websites to deliver more relevant advertising to you across multiple websites. If you do not allow these cookies, visits to this website will not be shared with advertising partners and will not contribute to targeted advertising on other websites.',
	    'request_format' => 'none',
	  ],
	],
];
