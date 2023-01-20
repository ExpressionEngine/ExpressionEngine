/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * @module readmore/readmore
 */

import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import ReadMoreEditing from './readmoreediting';
import ReadMoreUI from './readmoreui';

/**
 * The page break feature.
 *
 * It provides the possibility to insert a page break into the rich-text editor.
 *
 * For a detailed overview, check the {@glink features/readmore Page break feature} documentation.
 *
 * @extends module:core/plugin~Plugin
 */
export default class ReadMore extends Plugin {
	/**
	 * @inheritDoc
	 */
	static get requires() {
		return [ ReadMoreEditing, ReadMoreUI ];
	}

	/**
	 * @inheritDoc
	 */
	static get pluginName() {
		return 'ReadMore';
	}
}
