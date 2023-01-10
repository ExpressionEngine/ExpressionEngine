/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * @module filemanager/filemanagerediting
 */

import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import ImageEditing from '@ckeditor/ckeditor5-image/src/image/imageediting';
import LinkEditing from '@ckeditor/ckeditor5-link/src/linkediting';
import Notification from '@ckeditor/ckeditor5-ui/src/notification/notification';

import FileManagerCommand from './filemanagercommand';

/**
 * The FileManager editing feature. It introduces the {@link module:filemanager/filemanagercommand~FileManagerCommand FileManager command}.
 *
 * @extends module:core/plugin~Plugin
 */
export default class FileManagerEditing extends Plugin {
	/**
	 * @inheritDoc
	 */
	static get pluginName() {
		return 'FileManagerEditing';
	}

	/**
	 * @inheritDoc
	 */
	static get requires() {
		return [ Notification, ImageEditing, LinkEditing ];
	}

	/**
	 * @inheritDoc
	 */
	init() {
		const editor = this.editor;

		editor.commands.add( 'filemanager', new FileManagerCommand( editor ) );
	}
}
