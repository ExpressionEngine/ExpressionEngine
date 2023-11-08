/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * @module readmore/readmoreui
 */

import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';
import readMoreIcon from '../theme/icons/readmore.svg';

/**
 * The page break UI plugin.
 *
 * @extends module:core/plugin~Plugin
 */
export default class ReadMoreUI extends Plugin {
	init() {
		const editor = this.editor;
		const t = editor.t;

		// Add readMore button to feature components.
		editor.ui.componentFactory.add( 'readMore', locale => {
			const command = editor.commands.get( 'readMore' );
			const view = new ButtonView( locale );

			view.set( {
				label: t( 'Read more' ),
				icon: readMoreIcon,
				tooltip: true
			} );

			view.bind( 'isEnabled' ).to( command, 'isEnabled' );

			// Execute command.
			this.listenTo( view, 'execute', () => {
				editor.execute( 'readMore' );
				editor.editing.view.focus();
			} );

			return view;
		} );
	}
}
