/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * @module filemanager/filemanager
 */

import Plugin from '@ckeditor/ckeditor5-core/src/plugin';

import FileManagerUI from './filemanagerui';
import FileManagerEditing from './filemanagerediting';

/**
 * The FileManager feature, a bridge between the CKEditor 5 WYSIWYG editor and the
 * [FileManager](https://ckeditor.com/filemanager) file manager and uploader.
 *
 * This is a "glue" plugin which enables:
 *
 * * {@link module:filemanager/filemanagerediting~FileManagerEditing},
 * * {@link module:filemanager/filemanagerui~FileManagerUI},
 * * {@link module:adapter-filemanager/uploadadapter~FileManagerUploadAdapter}.
 *
 * See the {@glink features/image-upload/filemanager "FileManager integration" guide} to learn how to configure
 * and use this feature.
 *
 * Check out the {@glink features/image-upload/image-upload comprehensive "Image upload" guide} to learn about
 * other ways to upload images into CKEditor 5.
 *
 * @extends module:core/plugin~Plugin
 */
export default class FileManager extends Plugin {
	/**
	 * @inheritDoc
	 */
	static get pluginName() {
		return 'FileManager';
	}

	/**
	 * @inheritDoc
	 */
	static get requires() {
		return [ FileManagerEditing, FileManagerUI ];
	}
}

/**
 * The configuration of the {@link module:filemanager/filemanager~FileManager FileManager feature}.
 *
 * Read more in {@link module:filemanager/filemanager~FileManagerConfig}.
 *
 * @member {module:filemanager/filemanager~FileManagerConfig} module:core/editor/editorconfig~EditorConfig#filemanager
 */

/**
 * The configuration of the {@link module:filemanager/filemanager~FileManager FileManager feature}
 * and its {@link module:adapter-filemanager/uploadadapter~FileManagerUploadAdapter upload adapter}.
 *
 *		ClassicEditor
 *			.create( editorElement, {
 *				filemanager: {
 *					options: {
 *						resourceType: 'Images'
 *					}
 *				}
 *			} )
 *			.then( ... )
 *			.catch( ... );
 *
 * See {@link module:core/editor/editorconfig~EditorConfig all editor options}.
 *
 * @interface FileManagerConfig
 */

/**
 * The configuration options passed to the FileManager file manager instance.
 *
 * Check the file manager [documentation](https://ckeditor.com/docs/filemanager/filemanager3/#!/api/FileManager.Config-cfg-language)
 * for the complete list of options.
 *
 * @member {Object} module:filemanager/filemanager~FileManagerConfig#options
 */

/**
 * The type of the FileManager opener method.
 *
 * Supported types are:
 *
 * * `'modal'` &ndash; Opens FileManager in a modal,
 * * `'popup'` &ndash; Opens FileManager in a new "pop-up" window.
 *
 * Defaults to `'modal'`.
 *
 * @member {String} module:filemanager/filemanager~FileManagerConfig#openerMethod
 */

/**
 * The path (URL) to the connector which handles the file upload in FileManager file manager.
 * When specified, it enables the automatic upload of resources such as images inserted into the content.
 *
 * For instance, to use FileManager's
 * [quick upload](https://ckeditor.com/docs/filemanager/filemanager3-php/commands.html#command_quick_upload)
 * command, your can use the following (or similar) path:
 *
 *		ClassicEditor
 *			.create( editorElement, {
 *				filemanager: {
 *					uploadUrl: '/filemanager/core/connector/php/connector.php?command=QuickUpload&type=Files&responseType=json'
 *				}
 *			} )
 *			.then( ... )
 *			.catch( ... );
 *
 * Used by the {@link module:adapter-filemanager/uploadadapter~FileManagerUploadAdapter upload adapter}.
 *
 * @member {String} module:filemanager/filemanager~FileManagerConfig#uploadUrl
 */
