/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * @module readmore/readmorecommand
 */

import Command from '@ckeditor/ckeditor5-core/src/command';
import { findOptimalInsertionRange } from '@ckeditor/ckeditor5-widget/src/utils';

/**
 * The page break command.
 *
 * The command is registered by {@link module:readmore/readmoreediting~ReadMoreEditing} as `'readMore'`.
 *
 * To insert a page break at the current selection, execute the command:
 *
 *		editor.execute( 'readMore' );
 *
 * @extends module:core/command~Command
 */
export default class ReadMoreCommand extends Command {
	/**
	 * @inheritDoc
	 */
	refresh() {
		this.isEnabled = isReadMoreAllowed( this.editor.model );
	}

	/**
	 * Executes the command.
	 *
	 * @fires execute
	 */
	execute() {
		const model = this.editor.model;

		model.change( writer => {
			const readMoreElement = writer.createElement( 'readMore' );

			model.insertContent( readMoreElement );

			let nextElement = readMoreElement.nextSibling;

			// Check whether an element next to the inserted page break is defined and can contain a text.
			const canSetSelection = nextElement && model.schema.checkChild( nextElement, '$text' );

			// If the element is missing, but a paragraph could be inserted next to the page break, let's add it.
			if ( !canSetSelection && model.schema.checkChild( readMoreElement.parent, 'paragraph' ) ) {
				nextElement = writer.createElement( 'paragraph' );

				model.insertContent( nextElement, writer.createPositionAfter( readMoreElement ) );
			}

			// Put the selection inside the element, at the beginning.
			if ( nextElement ) {
				writer.setSelection( nextElement, 0 );
			}
		} );
	}
}

// Checks if the `readMore` element can be inserted at the current model selection.
//
// @param {module:engine/model/model~Model} model
// @returns {Boolean}
function isReadMoreAllowed( model ) {
	const schema = model.schema;
	const selection = model.document.selection;

	return isReadMoreAllowedInParent( selection, schema, model ) &&
		!checkSelectionOnObject( selection, schema );
}

// Checks if a page break is allowed by the schema in the optimal insertion parent.
//
// @param {module:engine/model/selection~Selection|module:engine/model/documentselection~DocumentSelection} selection
// @param {module:engine/model/schema~Schema} schema
// @param {module:engine/model/model~Model} model Model instance.
// @returns {Boolean}
function isReadMoreAllowedInParent( selection, schema, model ) {
	const parent = getInsertReadMoreParent( selection, model );

	return schema.checkChild( parent, 'readMore' );
}

// Checks if the selection is on object.
//
// @param {module:engine/model/selection~Selection|module:engine/model/documentselection~DocumentSelection} selection
// @param {module:engine/model/schema~Schema} schema
// @returns {Boolean}
function checkSelectionOnObject( selection, schema ) {
	const selectedElement = selection.getSelectedElement();

	return selectedElement && schema.isObject( selectedElement );
}

// Returns a node that will be used to insert a page break with `model.insertContent` to check if the page break can be placed there.
//
// @param {module:engine/model/selection~Selection|module:engine/model/documentselection~DocumentSelection} selection
// @param {module:engine/model/model~Model} model Model instance.
// @returns {module:engine/model/element~Element}
function getInsertReadMoreParent( selection, model ) {
	const insertAt = findOptimalInsertionRange( selection, model );

	const parent = insertAt.start.parent;

	if ( parent.isEmpty && !parent.is( '$root' ) ) {
		return parent.parent;
	}

	return parent;
}
