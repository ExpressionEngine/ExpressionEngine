/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/* global window */

/**
 * @module filemanager/filemanagercommand
 */

import Command from '@ckeditor/ckeditor5-core/src/command';
import CKEditorError from '@ckeditor/ckeditor5-utils/src/ckeditorerror';

/**
 * The FileManager command. It is used by the {@link module:filemanager/filemanagerediting~FileManagerEditing FileManager editing feature}
 * to open the FileManager file manager to insert an image or a link to a file into the editor content.
 *
 *		editor.execute( 'filemanager' );
 *
 * **Note:** This command uses other features to perform tasks:
 * - To insert images the {@link module:image/image/imageinsertcommand~ImageInsertCommand 'imageInsert'} command
 * from the {@link module:image/image~Image Image feature}.
 * - To insert links to files the {@link module:link/linkcommand~LinkCommand 'link'} command
 * from the {@link module:link/link~Link Link feature}.
 *
 * @extends module:core/command~Command
 */
export default class FileManagerCommand extends Command {
	/**
	 * @inheritDoc
	 */
	constructor( editor ) {
		super( editor );

		// Remove default document listener to lower its priority.
		this.stopListening( this.editor.model.document, 'change' );

		// Lower this command listener priority to be sure that refresh() will be called after link & image refresh.
		this.listenTo( this.editor.model.document, 'change', () => this.refresh(), { priority: 'low' } );
	}

	/**
	 * @inheritDoc
	 */
	refresh() {
		const imageCommand = this.editor.commands.get( 'imageInsert' );
		const linkCommand = this.editor.commands.get( 'link' );

		// The FileManager command is enabled when one of image or link command is enabled.
		this.isEnabled = imageCommand.isEnabled || linkCommand.isEnabled;
	}

	/**
	 * @inheritDoc
	 */
	execute() {
		const editor = this.editor;

		const openerMethod = this.editor.config.get( 'filemanager.openerMethod' ) || 'modal';

		if ( openerMethod != 'popup' && openerMethod != 'modal' ) {
			throw new CKEditorError( 'filemanager-unknown-openerMethod: The openerMethod config option must by "popup" or "modal".', editor );
		}

		const options = this.editor.config.get( 'filemanager.options' ) || {};

		options.chooseFiles = true;

		// Cache the user-defined onInit method
		const originalOnInit = options.onInit;

		// Pass the lang code to the FileManager if not defined by user.
		if ( !options.language ) {
			options.language = editor.locale.uiLanguage;
		}

		// The onInit method allows to extend FileManager's behavior. It is used to attach event listeners to file choosing related events.
		options.onInit = filemanager => {
			// Call original options.onInit if it was defined by user.
			if ( originalOnInit ) {
				originalOnInit( filemanager );
			}
		};

		function pickFile(evt) {
			const file = evt.detail;
			if (Object.prototype.toString.call(file) === '[object String]') {
				insertImages( editor, [file] );
			} else if (file.file_id) {
				const file_url = EE.Rte.filedirUrls[file.upload_location_id] + file.file_name;
				if (!file.isImage && !file.isSVG) {
					editor.execute( 'link', file.path );
				} else {
					insertImages( editor, [file.path] );
				}
			}
			window.document.removeEventListener( 'filepicker:pick', pickFile );
		}

		window.document.addEventListener( 'filepicker:pick', pickFile );

		window.Rte_browseImages(editor.sourceElement, options);
	}
}



function insertImages( editor, urls ) {
	const imageCommand = editor.commands.get( 'imageInsert' );

	// Check if inserting an image is actually possible - it might be possible to only insert a link.
	if ( !imageCommand.isEnabled ) {
		const notification = editor.plugins.get( 'Notification' );
		const t = editor.locale.t;

		notification.showWarning( t( 'Could not insert image at the current position.' ), {
			title: t( 'Inserting image failed' ),
			namespace: 'filemanager'
		} );

		return;
	}

	editor.execute( 'imageInsert', { source: urls } );
}
