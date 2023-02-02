/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * @module filemanager/filemanagerui
 */

import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';

import browseFilesIcon from '@ckeditor/ckeditor5-core/theme/icons/image.svg';

/**
 * The FileManager UI plugin. It introduces the `'filemanager'` toolbar button.
 *
 * @extends module:core/plugin~Plugin
 */
export default class FileManagerUI extends Plugin {
	/**
	 * @inheritDoc
	 */
	static get pluginName() {
		return 'FileManagerUI';
	}

	/**
	 * @inheritDoc
	 */
	init() {
		const editor = this.editor;
		const componentFactory = editor.ui.componentFactory;
		const t = editor.t;

		componentFactory.add( 'filemanager', locale => {
			const command = editor.commands.get( 'filemanager' );

			const button = new ButtonView( locale );

			button.set( {
				label: t( 'Insert image or file' ),
				icon: browseFilesIcon,
				tooltip: true
			} );

			button.bind( 'isEnabled' ).to( command );

			button.on( 'execute', () => {
				editor.execute( 'filemanager' );
				editor.editing.view.focus();
			} );

			return button;
		} );
	}
}
