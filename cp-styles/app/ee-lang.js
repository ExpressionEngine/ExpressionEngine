/*!
 * This source file is part of the open source project
 * ExpressionEngine User Guide (https://github.com/ExpressionEngine/ExpressionEngine-User-Guide)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

module.exports = function(hljs) {

	var ATTR_ASSIGNMENT = {
		illegal: /\}/,
		begin: /[a-zA-Z0-9_]+=/,
		returnBegin: true,
		relevance: 0,
		contains: [{
			className: 'attr',
			begin: /[a-zA-Z0-9_]+/
		}]
	}

	return {
		aliases: ['expressionengine', 'eecms'],
		case_insensitive: true,
		subLanguage: 'xml',
		contains: [
			hljs.COMMENT('{!--', '--}'),
			{
				className: 'template-tag',
				begin: /{\/?/,
				end: /}/,
				contains: [
					hljs.QUOTE_STRING_MODE,
					hljs.APOS_STRING_MODE,
					ATTR_ASSIGNMENT,
					hljs.NUMBER_MODE,
					{
						className: 'name',
						begin: /[a-zA-Z][a-zA-Z:\.\-_0-9]+/,
						lexemes: '[a-zA-Z][a-zA-Z0-9_:]*',
						relevance: 0,
						keywords: {
							keyword: 'if if:else if:elseif',
						},
						starts: {
							endsWithParent: true,
							relevance: 0,
							keywords: {
								keyword: 'and or xor',
								literal: 'false true null'
							},
							contains: [
								hljs.QUOTE_STRING_MODE,
								hljs.APOS_STRING_MODE,
								ATTR_ASSIGNMENT,
								hljs.NUMBER_MODE
							]
						}
					}
				]
			},
		]
	}
}
