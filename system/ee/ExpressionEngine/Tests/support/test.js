/* jslint node: true */
'use strict';

var properties = require('./build.json'),
	paths = {
		"builds": "/tmp/builds/",
		"docs": "/tmp/builds/EEDocs" + properties.tag + '/',
		"app": "/tmp/builds/ExpressionEngine" + properties.tag.replace('\\/', '-') + '/'
	};
