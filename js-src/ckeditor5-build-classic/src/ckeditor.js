/**
 * @license Copyright (c) 2003-2021, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */

// The editor creator to use.
import ClassicEditorBase from '@ckeditor/ckeditor5-editor-classic/src/classiceditor';

import Essentials from '@ckeditor/ckeditor5-essentials/src/essentials';
import Autoformat from '@ckeditor/ckeditor5-autoformat/src/autoformat';
import Clipboard from '@ckeditor/ckeditor5-clipboard/src/clipboard';
import Strikethrough from '@ckeditor/ckeditor5-basic-styles/src/strikethrough';
import Bold from '@ckeditor/ckeditor5-basic-styles/src/bold';
import Italic from '@ckeditor/ckeditor5-basic-styles/src/italic';
import Underline from '@ckeditor/ckeditor5-basic-styles/src/underline';
import Code from '@ckeditor/ckeditor5-basic-styles/src/code';
import CodeBlock from '@ckeditor/ckeditor5-code-block/src/codeblock';
import Subscript from '@ckeditor/ckeditor5-basic-styles/src/subscript';
import Superscript from '@ckeditor/ckeditor5-basic-styles/src/superscript';
import BlockQuote from '@ckeditor/ckeditor5-block-quote/src/blockquote';
import { Image, ImageInsert, AutoImage, ImageCaption, ImageStyle, ImageToolbar, ImageResize } from '@ckeditor/ckeditor5-image';
import Indent from '@ckeditor/ckeditor5-indent/src/indent';
import IndentBlock from '@ckeditor/ckeditor5-indent/src/indentblock';
import Link from '@ckeditor/ckeditor5-link/src/link';
import LinkImage from '@ckeditor/ckeditor5-link/src/linkimage';
import { List, ListProperties } from '@ckeditor/ckeditor5-list';
import MediaEmbed from '@ckeditor/ckeditor5-media-embed/src/mediaembed';
import HtmlEmbed from '@ckeditor/ckeditor5-html-embed/src/htmlembed';
import Paragraph from '@ckeditor/ckeditor5-paragraph/src/paragraph';
import PasteFromOffice from '@ckeditor/ckeditor5-paste-from-office/src/pastefromoffice';
import { Table, TableCellProperties, TableProperties, TableToolbar, TableColumnResize, TableCaption } from '@ckeditor/ckeditor5-table';
import TextTransformation from '@ckeditor/ckeditor5-typing/src/texttransformation';
import RemoveFormat from '@ckeditor/ckeditor5-remove-format/src/removeformat';
import HorizontalLine from '@ckeditor/ckeditor5-horizontal-line/src/horizontalline';
import SpecialCharacters from '@ckeditor/ckeditor5-special-characters/src/specialcharacters';
import SpecialCharactersEssentials from '@ckeditor/ckeditor5-special-characters/src/specialcharactersessentials';
import Heading from '@ckeditor/ckeditor5-heading/src/heading';
import PageBreak from '@ckeditor/ckeditor5-page-break/src/pagebreak';
import Font from '@ckeditor/ckeditor5-font/src/font';
import Alignment from '@ckeditor/ckeditor5-alignment/src/alignment';
import WordCount from '@ckeditor/ckeditor5-word-count/src/wordcount';
import { Style } from '@ckeditor/ckeditor5-style';
import { GeneralHtmlSupport } from '@ckeditor/ckeditor5-html-support';
import { SourceEditing } from '@ckeditor/ckeditor5-source-editing';
import { ShowBlocks } from '@ckeditor/ckeditor5-show-blocks';
import { FindAndReplace } from '@ckeditor/ckeditor5-find-and-replace';

import Mention from '../plugins/ckeditor5-mention/src/mention';
import ReadMore from '../plugins/readmore/src/readmore';
import FileManager from '../plugins/filemanager/src/filemanager';
import PageLinks from '../plugins/pagelinks/src/pagelinks';

export default class ClassicEditor extends ClassicEditorBase {}

// Plugins to include in the build.
ClassicEditor.builtinPlugins = [
	Essentials,
	FindAndReplace,
	Autoformat,
	Clipboard,
	Strikethrough,
	Underline,
	Code,
	CodeBlock,
	Subscript,
	Superscript,
	Bold,
	Italic,
	BlockQuote,
	FileManager,
	Heading,
	Image,
	ImageCaption,
	ImageStyle,
	ImageToolbar,
	ImageResize,
	ImageInsert,
	AutoImage,
	Indent,
	IndentBlock,
	Link,
	LinkImage,
	List,
	ListProperties,
	MediaEmbed,
	HtmlEmbed,
	Paragraph,
	PasteFromOffice,
	Table,
	TableToolbar,
	TableProperties,
	TableCellProperties,
	TableColumnResize,
	TableCaption,
	TextTransformation,
	ReadMore,
	RemoveFormat,
	Table,
	TableToolbar,
	HorizontalLine,
	SpecialCharacters,
	SpecialCharactersEssentials,
	Heading,
	PageBreak,
	Font,
	Alignment,
	Style,
	GeneralHtmlSupport,
	Mention,
	PageLinks,
	EditorClassPlugin,
	WordCount,
	SourceEditing,
	ShowBlocks
];

// Editor configuration.
ClassicEditor.defaultConfig = {
	toolbar: {
		items: [
			'heading',
			'|',
			'bold',
			'italic',
			'link',
			'bulletedList',
			'numberedList',
			'|',
			'indent',
			'outdent',
			'|',
			'imageUpload',
			'blockQuote',
			'insertTable',
			'mediaEmbed',
			'htmlEmbed',
			'undo',
			'redo'
		]
	},
	image: {
		toolbar: [
			'imageStyle:full',
			'imageStyle:side',
			'|',
			'imageTextAlternative'
		]
	},
	table: {
		contentToolbar: [
			'tableColumn',
			'tableRow',
			'mergeTableCells'
		]
	},
	// This value must be kept in sync with the language defined in webpack.config.js.
	language: 'en'
};

function EditorClassPlugin( editor ) {
	const className = editor.config.get( 'editorClass' );

	editor.ui.on('ready', () => {
		// For all balloons and popups to inherit from.
		editor.ui.view.body._bodyCollectionContainer.classList.add( className );

		// Note: Balloon editor doesn't have one.
		if (editor.ui.view.element) {
			editor.ui.view.element.classList.add( className );
		}
	});

	// For the editing root. In the Classic editor, it slightly duplicates with the class set on 
	// editor.ui.view.element because editor.ui.view.element contains the editing root. In the Balloon editor, 
	// which does not have the UI container (view), this makes perfect sense, though.
	editor.editing.view.change(writer => {
		writer.addClass( className, editor.editing.view.document.getRoot() );
	});
}
