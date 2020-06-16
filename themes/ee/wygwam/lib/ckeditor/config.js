/**
 * Copyright (c) 2003-2016, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
};

/*
 * --------------------------------------------------------------------
 *  OUTPUT FORMATTING
 * --------------------------------------------------------------------
 *
 * You can customize how CKEditor formats your HTML markup by setting
 * custom writer rules. Just uncomment the CKEDITOR.on() block below,
 * and modify the values for intent, breakBeforeOpen, etc.
 * See http://docs.cksource.com/CKEditor_3.x/Developers_Guide/Output_Formatting
 *
 */
//CKEDITOR.on( 'instanceReady', function( ev )
//{
//	var blockTagRules = {
//		indent: true, // indent the contents between the opening and closing tags?
//		breakBeforeOpen: true, // put a line break before the opening tag?
//		breakAfterOpen: true, // put a line break after the opening tag?
//		breakBeforeClose: false, // put a line break before the closing tag?
//		breakAfterClose: true // put a line break after the closing tag?
//	};
//
//	var blockTags = ['div','h1','h2','h3','h4','h5','h6','p','pre'];
//	for (var i = 0; i < blockTags.length; i++)
//		ev.editor.dataProcessor.writer.setRules( blockTags[i], blockTagRules);
//});

