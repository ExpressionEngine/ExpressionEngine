/*  WysiHat - WYSIWYG JavaScript framework, version 0.2.1
 *  (c) 2008-2010 Joshua Peek
 *  JQ-WysiHat - jQuery port of WysiHat to run on jQuery
 *  (c) 2010 Scott Williams & Aaron Gustafson
 *
 *  WysiHat is freely distributable under the terms of an MIT-style license.
 *--------------------------------------------------------------------------*/


var WysiHat = {
	name:	'WysiHat'
};
(function($){

	var
	WYSIHAT 	= WysiHat.name,
	EDITOR		= '-editor',
	FIELD		= '-field',
	CHANGE		= ':change',
	CLASS		= WYSIHAT + EDITOR,
	ID			= 'id',
	E_EVT		= CLASS + CHANGE,
	F_EVT		= WYSIHAT + FIELD + CHANGE,
	IMMEDIATE	= ':immediate',

	INDEX		= 0,
	NULL		= null,
	EMPTY		= '';

	WysiHat.Editor = {

		attach: function( $field )
		{

			var
			t_id	= $field.attr( ID ),
			e_id	= ( t_id != EMPTY ? t_id : WYSIHAT + INDEX++ ) + EDITOR,
			fTimer	= NULL,
			eTimer	= NULL,
			$editor	= $( '#' + e_id );

			if ( t_id == EMPTY )
			{
				t_id = e_id.replace( EDITOR, FIELD );
				$field.attr( ID, t_id );
			}

			if ( $editor.length )
			{
				if ( ! $editor.hasClass( CLASS ) )
				{
					$editor.addClass( CLASS );
				}
				return $editor;
			}
			else
			{
				$editor = $('<div id="' + e_id + '" class="' + CLASS + '" contentEditable="true" role="application"></div>')
									.html( WysiHat.Formatting.getBrowserMarkupFrom( $field ) )
									.data( 'field', $field );

				$.extend( $editor, WysiHat.Commands );

				function updateField()
				{
					$field.val( WysiHat.Formatting.getApplicationMarkupFrom( $editor ) );
					this.fTimer = null;
				}
				function updateEditor()
				{
					$editor.html( WysiHat.Formatting.getBrowserMarkupFrom( $field ) );
					this.eTimer = null;
				}

				$field
					.data( 'editor', $editor )
					.bind('keyup mouseup',function(){
						$field.trigger(F_EVT);
					 })
					.bind( F_EVT, function(){
						if ( this.fTimer )
						{
							clearTimeout( this.fTimer );
						}
						this.fTimer = setTimeout(updateEditor, 250 );
					 })
					.bind( F_EVT + IMMEDIATE, updateEditor )
					.hide()
					.before(
						$editor
							.bind('keyup mouseup',function(){
								$editor.trigger(E_EVT);
							 })
							.bind( E_EVT, function(){
								if ( this.eTimer )
								{
									clearTimeout( this.eTimer );
								}
								this.eTimer = setTimeout(updateField, 250 );
							 })
							.bind( E_EVT + IMMEDIATE, updateField )
					 )

			}


			return $editor;
		}
	};

})(jQuery);
WysiHat.BrowserFeatures = (function($){

	var features = {};

	function createTmpIframe(callback)
	{
	    var
		frameDocument,
	    $frame	= $('<iframe></iframe>'),
		frame	= $frame.get(0);

	    $frame
			.css({
	      		position: 'absolute',
	      		left: '-1000px'
	    	 })
			.load(function(){
				if ( typeof frame.contentDocument !== 'undefined' )
				{
					frameDocument = frame.contentDocument;
				}
				else if ( typeof frame.contentWindow !== 'undefined' &&
						  typeof frame.contentWindow.document !== 'undefined' )
				{
	        		frameDocument = frame.contentWindow.document;
				}
				frameDocument.designMode = 'on';
				callback(frameDocument);
				$frame.remove();
			 });
	    $('body').append($frame);
	}

  	function detectParagraphType(doc)
	{
		var tagName;

    	doc.body.innerHTML = '';
    	doc.execCommand('insertparagraph', false, null);

    	element = doc.body.childNodes[0];
    	if (element && element.tagName)
		{
	      tagName = element.tagName.toLowerCase();
		}

    	if (tagName == 'div')
		{
	    	features.paragraphType = "div";
		}
		else if (doc.body.innerHTML == "<p><br></p>")
		{
			features.paragraphType = "br";
		}
		else
		{
			features.paragraphType = "p";
		}
	}

	function detectIndentType(doc)
	{
		var tagName;

		doc.body.innerHTML = 'tab';
		doc.execCommand('indent', false, null);

		element = doc.body.childNodes[0];
		if (element && element.tagName)
		{
			tagName = element.tagName.toLowerCase();
		}

		features.indentInsertsBlockquote = (tagName == 'blockquote');
	}

	features.run = function run()
	{
		if (features.finished) return;

		createTmpIframe(function(document){
			detectParagraphType(document);
			detectIndentType(document);
			features.finished = true;
		});
	};

	return features;

})(jQuery);
WysiHat.Element = (function( $ ){

	var
	FALSE = false,

	roots			= [ 'blockquote', 'details', 'fieldset', 'figure', 'td' ],

	sections		= [ 'article', 'aside', 'header', 'footer', 'nav', 'section' ],

	containers		= [ 'blockquote', 'details', 'dl', 'ol', 'table', 'ul' ],

	sub_containers	= [ 'dd', 'dt', 'li', 'summary', 'td', 'th' ],

	content			= [ 'address', 'caption', 'dd', 'div', 'dt', 'figcaption', 'figure', 'h1', 'h2', 'h3',
						'h4', 'h5', 'h6', 'hgroup', 'hr', 'p', 'pre', 'summary', 'small' ],

	media			= [ 'audio', 'canvas', 'embed', 'iframe', 'img', 'object', 'param', 'source', 'track', 'video' ],

	phrases			= [ 'a', 'abbr', 'b', 'br', 'cite', 'code', 'del', 'dfn', 'em', 'i', 'ins', 'kbd',
	 					'mark', 'span', 'q', 'samp', 's', 'strong', 'sub', 'sup', 'time', 'u', 'var', 'wbr' ],

	formatting		= [ 'b', 'code', 'del', 'em', 'i', 'ins', 'kbd', 'span', 's', 'strong', 'u' ],

	html4_blocks	= [ 'address', 'blockquote', 'div', 'dd', 'dt', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'pre' ],

	forms			= [ 'button', 'datalist', 'fieldset', 'form', 'input', 'keygen', 'label',
						'legend', 'optgroup', 'option', 'output', 'select', 'textarea' ];

	function is( $el )
	{
		var
		i	= arguments.length,
		ret	= FALSE;
		while ( ret == FALSE &&
				i-- > 1 )
		{
			ret	= $el.is( arguments[i].join(',') );
		}
		return ret;
	}

	function isRoot( $el )
	{
		return is( $el, roots );
	}
	function isSection( $el )
	{
		return is( $el, sections );
	}
	function isContainer( $el )
	{
		return is( $el, containers );
	}
	function isSubContainer( $el )
	{
		return is( $el, sub_containers );
	}
	function isBlock( $el )
	{
		return is( $el, roots, sections, containers, sub_containers, content );
	}
	function isHTML4Block( $el )
	{
		return is( $el, html4_blocks );
	}
	function isContentElement( $el )
	{
		return is( $el, sub_containers, content );
	}
	function isMediaElement( $el )
	{
		return is( $el, media );
	}
	function isPhraseElement( $el )
	{
		return is( $el, phrases );
	}
	function isFormatter( $el )
	{
		return is( $el, formatting );
	}
	function isFormComponent( $el )
	{
		return is( $el, forms );
	}

	function getRoots()
	{
		return roots;
	}
	function getSections( $el )
	{
		return sections;
	}
	function getContainers()
	{
		return containers;
	}
	function getSubContainers()
	{
		return sub_containers;
	}
	function getBlocks()
	{
		return roots.concat( sections, containers, sub_containers, content );
	}
	function getHTML4Blocks()
	{
		return html4_blocks;
	}
	function getContentElements()
	{
		return sub_containers.concat(  content );
	}
	function getMediaElements()
	{
		return media;
	}
	function getPhraseElements()
	{
		return phrases;
	}
	function getFormatters()
	{
		return formatting;
	}
	function getFormComponents()
	{
		return forms;
	}

	return {
		isRoot:				isRoot,
		isSection:			isSection,
		isContainer:		isContainer,
		isSubContainer:		isSubContainer,
		isBlock:			isBlock,
		isHTML4Block:		isHTML4Block,
		isContentElement:	isContentElement,
		isMediaElement:		isMediaElement,
		isPhraseElement:	isPhraseElement,
		isFormatter:		isFormatter,
		isFormComponent:	isFormComponent,
		getRoots:			getRoots,
		getSections:		getSections,
		getContainers:		getContainers,
		getSubContainers:	getSubContainers,
		getBlocks:			getBlocks,
		getHTML4Blocks:		getHTML4Blocks,
		getContentElements:	getContentElements,
		getMediaElements:	getMediaElements,
		getPhraseElements:	getPhraseElements,
		getFormatters:		getFormatters,
		getFormComponents:	getFormComponents
	};

})( jQuery );
/*  IE Selection and Range classes
 *
 *  Original created by Tim Cameron Ryan
 *    http://github.com/timcameronryan/IERange
 *  Copyright (c) 2009 Tim Cameron Ryan
 *  Released under the MIT/X License
 *
 *  Modified by Joshua Peek
 */
if (!window.getSelection) {
	(function($){

		var
		NULL = null,
		FALSE	= false,

		DOMUtils = {
			isDataNode: function( node )
			{
				try {
					return node && node.nodeValue !== null && node.data !== null;
				} catch (e) {
					return false;
				}
			},
			isAncestorOf: function( parent, node )
			{
				if ( ! parent )
				{
					return false;
				}
				return ! DOMUtils.isDataNode(parent) &&
					   ( node.parentNode == parent ||
						 parent.contains( DOMUtils.isDataNode(node) ? node.parentNode : node ) );
			},
			isAncestorOrSelf: function( root, node )
			{
				return root == node ||
				 	   DOMUtils.isAncestorOf( root, node );
			},
			findClosestAncestor: function( root, node )
			{
				if ( DOMUtils.isAncestorOf( root, node ) )
				{
					while ( node && node.parentNode != root )
					{
						node = node.parentNode;
					}
				}
				return node;
			},
			getNodeLength: function(node)
			{
				return DOMUtils.isDataNode(node) ? node.length : node.childNodes.length;
			},
			splitDataNode: function( node, offset )
			{
				if ( ! DOMUtils.isDataNode( node ) )
				{
					return false;
				}
				var newNode = node.cloneNode(false);
				node.deleteData(offset, node.length);
				newNode.deleteData(0, offset);
				node.parentNode.insertBefore( newNode, node.nextSibling );
			}
		};

		window.Range = (function(){

			function Range( document )
			{
				this._document = document;

				this.startContainer = this.endContainer = document.body;
				this.endOffset = DOMUtils.getNodeLength(document.body);
			}

			function findChildPosition( node )
			{
				for ( var i = 0; node = node.previousSibling; i++ )
				{
					continue;
				}
				return i;
			}

			Range.prototype = {

				START_TO_START:	0,
				START_TO_END:	1,
				END_TO_END:		2,
				END_TO_START:	3,

				startContainer:	null,
				startOffset:	0,
				endContainer:	null,
				endOffset:		0,
				commonAncestorContainer: null,
				collapsed:		false,
				_document:		null,

				_toTextRange: function()
				{
					function adoptEndPoint( textRange, domRange, bStart )
					{
						var
						container		= domRange[bStart ? 'startContainer' : 'endContainer'],
						offset			= domRange[bStart ? 'startOffset' : 'endOffset'],
						textOffset		= 0,
						anchorNode		= DOMUtils.isDataNode(container) ? container : container.childNodes[offset],
						anchorParent	= DOMUtils.isDataNode(container) ? container.parentNode : container,
						cursorNode		= domRange._document.createElement('a'),
						cursor			= domRange._document.body.createTextRange();

						if ( container.nodeType == 3 ||
							 container.nodeType == 4 )
						{
							textOffset = offset;
						}


						textRange.setEndPoint(bStart ? 'StartToStart' : 'EndToStart', cursor);
						textRange[bStart ? 'moveStart' : 'moveEnd']('character', textOffset);
					}

					var textRange = this._document.body.createTextRange();
					adoptEndPoint(textRange, this, true);
					adoptEndPoint(textRange, this, false);
					return textRange;
				},

				_refreshProperties: function()
				{
					this.collapsed = (this.startContainer == this.endContainer && this.startOffset == this.endOffset);
					var node = this.startContainer;
					while ( node &&
							node != this.endContainer &&
							! DOMUtils.isAncestorOf(node, this.endContainer) )
					{
						node = node.parentNode;
					}
					this.commonAncestorContainer = node;
				},

				setStart: function( container, offset )
				{
					this.startContainer	= container;
					this.startOffset	= offset;
					this._refreshProperties();
				},
				setEnd: function( container, offset )
				{
					this.endContainer	= container;
					this.endOffset		= offset;
					this._refreshProperties();
				},
				setStartBefore: function( refNode )
				{
					this.setStart( refNode.parentNode, findChildPosition(refNode) );
				},
				setStartAfter: function(refNode)
				{
					this.setStart( refNode.parentNode, findChildPosition(refNode) + 1 );
				},
				setEndBefore: function( refNode )
				{
					this.setEnd(refNode.parentNode, findChildPosition(refNode));
				},
				setEndAfter: function( refNode )
				{
					this.setEnd( refNode.parentNode, findChildPosition(refNode) + 1 );
				},
				selectNode: function( refNode )
				{
					this.setStartBefore(refNode);
					this.setEndAfter(refNode);
				},
				selectNodeContents: function( refNode )
				{
					this.setStart(refNode, 0);
					this.setEnd(refNode, DOMUtils.getNodeLength(refNode));
				},
				collapse: function(toStart)
				{
					if (toStart)
					{
						this.setEnd(this.startContainer, this.startOffset);
					}
					else
					{
						this.setStart(this.endContainer, this.endOffset);
					}
				},
				cloneContents: function()
				{
					return (function cloneSubtree( iterator ){
						for ( var node, frag = document.createDocumentFragment(); node = iterator.next(); )
						{
							node = node.cloneNode( ! iterator.hasPartialSubtree() );
							if ( iterator.hasPartialSubtree() )
							{
								node.appendChild( cloneSubtree( iterator.getSubtreeIterator() ) );
							}
							frag.appendChild( node );
						}
						return frag;
					})( new RangeIterator(this) );
				},
				extractContents: function()
				{
					var range = this.cloneRange();
					if (this.startContainer != this.commonAncestorContainer)
					{
						this.setStartAfter(DOMUtils.findClosestAncestor(this.commonAncestorContainer, this.startContainer));
					}
					this.collapse(true);
					return (function extractSubtree( iterator ){
						for ( var node, frag = document.createDocumentFragment(); node = iterator.next(); )
						{
							iterator.hasPartialSubtree() ? node = node.cloneNode(false) : iterator.remove();
							if ( iterator.hasPartialSubtree() )
							{
								node.appendChild( extractSubtree( iterator.getSubtreeIterator() ) );
							}
							frag.appendChild( node );
						}
						return frag;
					})( new RangeIterator(range) );
				},
				deleteContents: function()
				{
					var range = this.cloneRange();
					if (this.startContainer != this.commonAncestorContainer)
					{
						this.setStartAfter( DOMUtils.findClosestAncestor( this.commonAncestorContainer, this.startContainer ) );
					}
					this.collapse(true);
					(function deleteSubtree( iterator ){
						while ( iterator.next() )
						{
							iterator.hasPartialSubtree() ? deleteSubtree( iterator.getSubtreeIterator() ) : iterator.remove();
						}
					})( new RangeIterator(range) );
				},
				insertNode: function(newNode)
				{
					if (DOMUtils.isDataNode(this.startContainer))
					{
						DOMUtils.splitDataNode( this.startContainer, this.startOffset );
						this.startContainer.parentNode.insertBefore( newNode, this.startContainer.nextSibling );
					}
					else
					{
						var offsetNode = this.startContainer.childNodes[this.startOffset];
						if (offsetNode)
						{
							this.startContainer.insertBefore( newNode, offsetNode );
						}
						else
						{
							this.startContainer.appendChild( newNode );
						}
					}
					this.setStart(this.startContainer, this.startOffset);
				},
				surroundContents: function(newNode)
				{
					var content = this.extractContents();
					this.insertNode(newNode);
					newNode.appendChild(content);
					this.selectNode(newNode);
				},
				compareBoundaryPoints: function(how, sourceRange)
				{
					var containerA, offsetA, containerB, offsetB;
					switch ( how )
					{
						case this.START_TO_START:
						case this.START_TO_END:
							containerA = this.startContainer;
							offsetA = this.startOffset;
							break;
						case this.END_TO_END:
						case this.END_TO_START:
							containerA = this.endContainer;
							offsetA = this.endOffset;
							break;
					}
					switch ( how )
					{
						case this.START_TO_START:
						case this.END_TO_START:
							containerB = sourceRange.startContainer;
							offsetB = sourceRange.startOffset;
							break;
						case this.START_TO_END:
						case this.END_TO_END:
							containerB = sourceRange.endContainer;
							offsetB = sourceRange.endOffset;
							break;
					}


					return ( containerA.sourceIndex < containerB.sourceIndex
								? -1
								: ( containerA.sourceIndex == containerB.sourceIndex
										? ( offsetA < offsetB
												? -1
												: ( offsetA == offsetB ? 0 : 1 )
										  ) // offsetA < offsetB
										: 1
								  ) // containerA.sourceIndex == containerB.sourceIndex
						   ); // containerA.sourceIndex < containerB.sourceIndex
				},
				cloneRange: function()
				{
					var range = new Range( this._document );
					range.setStart( this.startContainer, this.startOffset );
					range.setEnd( this.endContainer, this.endOffset );
					return range;
				},
				detach: function()
				{
				},
				toString: function()
				{
					return this._toTextRange().text;
				},
				createContextualFragment: function( tagString )
				{
					var
					content		= ( DOMUtils.isDataNode(this.startContainer) ? this.startContainer.parentNode
																			 : this.startContainer ).cloneNode(false),
					fragment	= this._document.createDocumentFragment();

					content.innerHTML = tagString;
					for ( ; content.firstChild; )
					{
						fragment.appendChild(content.firstChild);
					}
					return fragment;
				}
			};

			function RangeIterator(range)
			{
				this.range = range;
				if ( range.collapsed )
				{
					return;
				}

				var root	= range.commonAncestorContainer;
				this._next	= range.startContainer == root && ! DOMUtils.isDataNode( range.startContainer )
								? range.startContainer.childNodes[range.startOffset]
								: DOMUtils.findClosestAncestor( root, range.startContainer );
				this._end	= range.endContainer == root && ! DOMUtils.isDataNode( range.endContainer )
								? range.endContainer.childNodes[range.endOffset]
								: DOMUtils.findClosestAncestor( root, range.endContainer ).nextSibling;
			}

			RangeIterator.prototype = {

				range: null,
				_current: null,
				_next: null,
				_end: null,

				hasNext: function()
				{
					return !! this._next;
				},
				next: function()
				{
					var current	= this._current = this._next;
					this._next	= this._current && this._current.nextSibling != this._end ? this._current.nextSibling : null;

					if (DOMUtils.isDataNode(this._current))
					{
						if ( this.range.endContainer == this._current )
						{
							( current = current.cloneNode(true) ).deleteData( this.range.endOffset, current.length - this.range.endOffset );
						}
						if ( this.range.startContainer == this._current )
						{
							( current = current.cloneNode(true) ).deleteData( 0, this.range.startOffset );
						}
					}
					return current;
				},
				remove: function()
				{
					if ( DOMUtils.isDataNode(this._current) &&
						 ( this.range.startContainer == this._current ||
						   this.range.endContainer == this._current ) )
					{
						var
						start	= this.range.startContainer == this._current ? this.range.startOffset : 0,
						end		= this.range.endContainer == this._current ? this.range.endOffset : this._current.length;
						this._current.deleteData( start, end - start );
					}
					else
					{
						this._current.parentNode.removeChild( this._current );
					}
				},
				hasPartialSubtree: function()
				{
					return ! DOMUtils.isDataNode(this._current) &&
						   ( DOMUtils.isAncestorOrSelf( this._current, this.range.startContainer ) ||
							 DOMUtils.isAncestorOrSelf( this._current, this.range.endContainer ) );
				},
				getSubtreeIterator: function()
				{
					var subRange = new Range(this.range._document);
					subRange.selectNodeContents(this._current);
					if ( DOMUtils.isAncestorOrSelf(this._current, this.range.startContainer) )
					{
						subRange.setStart( this.range.startContainer, this.range.startOffset );
					}
					if ( DOMUtils.isAncestorOrSelf( this._current, this.range.endContainer ) )
					{
						subRange.setEnd(this.range.endContainer, this.range.endOffset);
					}
					return new RangeIterator(subRange);
				}
			};

			return Range;
		})();

		window.Range._fromTextRange = function( textRange, document )
		{
			function adoptBoundary(domRange, textRange, bStart)
			{
				var
				cursorNode	= document.createElement('a'),
				cursor		= textRange.duplicate(),
				parent;

				cursor.collapse(bStart);
				parent = cursor.parentElement();

				do {
					parent.insertBefore( cursorNode, cursorNode.previousSibling );
					cursor.moveToElementText( cursorNode );
				} while ( cursorNode.previousSibling &&
						  cursor.compareEndPoints( bStart ? 'StartToStart' : 'StartToEnd', textRange ) > 0 );

				if ( cursorNode.nextSibling &&
					 cursor.compareEndPoints(bStart ? 'StartToStart' : 'StartToEnd', textRange) == -1 )
				{
					cursor.setEndPoint( bStart ? 'EndToStart' : 'EndToEnd', textRange );
					domRange[bStart ? 'setStart' : 'setEnd']( cursorNode.nextSibling, cursor.text.length );
				}
				else
				{
					domRange[bStart ? 'setStartBefore' : 'setEndBefore'](cursorNode);
				}
				cursorNode.parentNode.removeChild(cursorNode);
			}

			var domRange = new Range(document);
			adoptBoundary(domRange, textRange, true);
			adoptBoundary(domRange, textRange, false);
			return domRange;
		};

		document.createRange = function()
		{
			return new Range(document);
		};

		window.Selection = (function(){
			function Selection(document)
			{
				this._document = document;

				var selection = this;
				document.attachEvent('onselectionchange', function(){
					selection._selectionChangeHandler();
				});

				setTimeout(function(){
					selection._selectionChangeHandler();
				},10);
			}

			Selection.prototype = {

				rangeCount: 0,
				_document:	null,
				anchorNode:	null,
				focusNode:	null,

				_selectionChangeHandler: function()
				{
					var
					range	= this._document.selection.createRange(),
					text	= range.text.split(/\r|\n/),
					$parent	= $( range.parentElement() ),
					a_re, $a, f_re, $f;

					if ( text.length > 1 )
					{
						a_re	= new RegExp( text[0] + '$' );
						f_re	= new RegExp( '^' + text[text.length-1] );

						$parent.children().each(function(){
							if ( $(this).text().match( a_re ) )
							{
								this.anchorNode = this;
							}
							if ( $(this).text().match( f_re ) )
							{
								this.focusNode = this;
							}
						});
					}
					else
					{
						this.anchorNode = $parent.get(0);
						this.focusNode	= this.anchorNode;
					}

					this.rangeCount = this._selectionExists( range ) ? 1 : 0;
				},
				_selectionExists: function( textRange )
				{
					return textRange.parentElement().isContentEditable ||
						   textRange.compareEndPoints('StartToEnd', textRange) != 0;
				},
				addRange: function(range)
				{
					var
					selection	= this._document.selection.createRange(),
					textRange	= range._toTextRange();
					if ( ! this._selectionExists(selection) )
					{
						try {
 							textRange.select();
						} catch(e) {}
					}
					else
					{
						if (textRange.compareEndPoints('StartToStart', selection) == -1)
						{
							if ( textRange.compareEndPoints('StartToEnd', selection) > -1 &&
								 textRange.compareEndPoints('EndToEnd', selection) == -1 )
							{
								selection.setEndPoint('StartToStart', textRange);
							}
						}
						else
						{
							if ( textRange.compareEndPoints('EndToStart', selection) < 1 &&
								 textRange.compareEndPoints('EndToEnd', selection) > -1 )
							{
								selection.setEndPoint('EndToEnd', textRange);
							}
						}
						selection.select();
					}
				},
				removeAllRanges: function()
				{
					this._document.selection.empty();
				},
				getRangeAt: function(index)
				{
					var textRange = this._document.selection.createRange();
					if ( this._selectionExists( textRange ) )
					{
						return Range._fromTextRange( textRange, this._document );
					}
					return null;
				},
				toString: function()
				{
					return this._document.selection.createRange().text;
				},
				isCollapsed: function()
				{
					var range = document.createRange();
					return range.collapsed;
				},
				deleteFromDocument: function()
				{
					var textRange = this._document.selection.createRange();
					textRange.pasteHTML('');
				}
			};

			return Selection;
		})();

		window.getSelection = (function(){
			var selection = new Selection(document);
			return function() { return selection; };
		})();

	})(jQuery);
}

jQuery.extend(Range.prototype, (function(){

	function beforeRange(range)
	{
		if ( ! range ||
			 ! range.compareBoundaryPoints )
		{
			return false;
		}
		return ( this.compareBoundaryPoints( this.START_TO_START, range ) == -1 &&
				 this.compareBoundaryPoints( this.START_TO_END, range ) == -1 &&
				 this.compareBoundaryPoints( this.END_TO_END, range ) == -1 &&
				 this.compareBoundaryPoints( this.END_TO_START, range ) == -1 );
	}

	function afterRange(range)
	{
		if ( ! range ||
			 ! range.compareBoundaryPoints )
		{
			return false;
		}
		return ( this.compareBoundaryPoints( this.START_TO_START, range ) == 1 &&
				 this.compareBoundaryPoints( this.START_TO_END, range ) == 1 &&
				 this.compareBoundaryPoints( this.END_TO_END, range ) == 1 &&
				 this.compareBoundaryPoints( this.END_TO_START, range ) == 1 );
	}

	function betweenRange(range)
	{
		if ( ! range ||
			 ! range.compareBoundaryPoints )
		{
			return false;
		}
		return ! ( this.beforeRange(range) || this.afterRange(range) );
	}

	function equalRange(range)
	{
		if ( ! range ||
			 ! range.compareBoundaryPoints )
		{
			return false;
		}
		return ( this.compareBoundaryPoints( this.START_TO_START, range ) == 0 &&
				 this.compareBoundaryPoints( this.START_TO_END, range ) == 1 &&
				 this.compareBoundaryPoints( this.END_TO_END, range ) == 0 &&
				 this.compareBoundaryPoints( this.END_TO_START, range ) == -1 );
	}

	function getNode()
	{
		var
		parent	= this.commonAncestorContainer,
		that	= this,
		child;

		while (parent.nodeType == Node.TEXT_NODE)
		{
			parent = parent.parentNode;
		}

		jQuery(parent).children().each(function(){
			var range = document.createRange();
			range.selectNodeContents(this);
			child = that.betweenRange(range);
		});

		return $(child || parent).get(0);
	}

	return {
		beforeRange:  beforeRange,
		afterRange:   afterRange,
		betweenRange: betweenRange,
		equalRange:   equalRange,
		getNode:      getNode
	};

})());

if ( typeof Selection == 'undefined' )
{
	var Selection = {};
	Selection.prototype = window.getSelection().__proto__;
}

(function( DOC, $ ){

	if ( $.browser.msie )
	{

		function getNode()
		{
			var range = this._document.selection.createRange();
			return $(range.parentElement());
		}

		function selectNode(element)
		{
			var range = this._document.body.createTextRange();
			range.moveToElementText(element);
			range.select();
		}

	}
	else
	{

		function getNode()
		{
			return ( this.rangeCount > 0 ) ? this.getRangeAt(0).getNode() : null;
		}

		function selectNode(element)
		{
			var range = document.createRange();
			range.selectNode(element[0]);
			this.removeAllRanges();
			this.addRange(range);
		}

	}

	$.extend(Selection.prototype, {
		getNode:    getNode,
		selectNode: selectNode
	});

})( document, jQuery );
(function($){

	$(document).ready(function(){

		var timer	= null,
			empty	= '<p>&nbsp;</p>',
			$element;

		function fieldChangeHandler( e )
		{
			if ( timer )
			{
				clearTimeout(timer);
			}

			$element = $(this);

			timer = setTimeout(function(){
				var
				element		= $element.get(0),
				val, evt;

				if ( $element.is('*[contenteditable=""],*[contenteditable=true]') )
				{
					val	= $element.html();

					if ( val == '' ||
					 	 val == '<br>' ||
					 	 val == '<br/>' )
					{
						val = empty;
						$element.html(val);
						selectEmptyParagraph($element);
					}

					evt	= 'editor:change';
				}
				else
				{
					val	= $element.val();
					evt	= 'field:change';
				}

				if ( val &&
					 element.previousValue != val )
				{
					$element.trigger( 'WysiHat-' + evt );
					element.previousValue = val;
				}
			}, 100);
		}

		function selectEmptyParagraph( $el )
		{
			var $el	= $element || $(this),
				s	= window.getSelection(),
				r	= document.createRange();
			if ( $el.html() == empty )
			{
				s.removeAllRanges();
				r.selectNodeContents( $el.find('p').get(0) );
				s.addRange(r);
			}
		}

		$('body')
			.delegate('input,textarea,*[contenteditable],*[contenteditable=true]', 'keydown', fieldChangeHandler )
			.delegate('*[contenteditable],*[contenteditable=true]', 'focus', selectEmptyParagraph );
	});

})(jQuery);

WysiHat.Commands = (function( WIN, DOC, $ ){

	var
	TRUE			= true,
	FALSE			= false,
	NULL			= null,
	UNDEFINED,
	OL				= 'ol',
	UL				= 'ul',
	WYSIHAT_EDITOR	= 'WysiHat-editor',
	CHANGE_EVT		= WYSIHAT_EDITOR + ':change',

	valid_cmds		= [ 'backColor', 'bold', 'createLink', 'fontName', 'fontSize', 'foreColor', 'hiliteColor',
						'italic', 'removeFormat', 'strikethrough', 'subscript', 'superscript', 'underline', 'unlink',
						'delete', 'formatBlock', 'forwardDelete', 'indent', 'insertHorizontalRule', 'insertHTML',
						'insertImage', 'insertLineBreak', 'insertOrderedList', 'insertParagraph', 'insertText',
						'insertUnorderedList', 'justifyCenter', 'justifyFull', 'justifyLeft', 'justifyRight', 'outdent',
						'copy', 'cut', 'paste', 'selectAll', 'styleWithCSS', 'useCSS' ],

	dflt_shortcuts	= {
						bold:		{ ctrl: true, keycode: 66 }, // b
						createLink: { ctrl: true, keycode: 76 }, // l
						italic:		{ ctrl: true, keycode: 73 }, // i
						underline:	{ ctrl: true, keycode: 85 }  // u
					  },

	block_els		= WysiHat.Element.getContentElements().join(',').replace( ',div,', ',div:not(.' + WYSIHAT_EDITOR + '),' );

	function boldSelection()
	{
		this.execCommand('bold', FALSE, NULL);
	}
	function isBold()
	{
		return ( selectionIsWithin('b,strong') || document.queryCommandState('bold') );
	}
	function underlineSelection()
	{
		this.execCommand('underline', FALSE, NULL);
	}
	function isUnderlined()
	{
		return ( selectionIsWithin('u,ins') || document.queryCommandState('underline') );
	}
	function italicizeSelection()
	{
		this.execCommand('italic', FALSE, NULL);
	}
	function isItalic()
	{
		return ( selectionIsWithin('i,em') || document.queryCommandState('italic') );
	}
	function strikethroughSelection()
	{
		this.execCommand('strikethrough', FALSE, NULL);
	}
	function isStruckthrough()
	{
		return ( selectionIsWithin('s,del') || document.queryCommandState('strikethrough') );
	}


	function quoteSelection()
	{
		var $quote = $('<blockquote/>');
		this.manipulateSelection(function( range, $quote ){
			var $q		= $quote.clone(),
				$els	= this.getRangeElements( range, block_els ),
				last	= $els.length - 1,
				$coll	= $();
			$els.each(function(i){
				var
				$this	= $(this),
				sub		= false,
				$el;

				if ( WysiHat.Element.isSubContainer( $this ) )
				{
					sub = true;
				}

				if ( ! i &&
					 sub &&
					 i == last )
				{
					$el = $('<p/>').html( $this.html() );
					$this.html('').append( $el );
					$coll = $coll.add($el);
				}
				else if ( sub )
				{
					$coll = $coll.add(
								$this.closest( WysiHat.Element.getContainers().join(",") )
							);
				}
				else
				{
					$coll = $coll.add($this);
				}

				if ( i == last )
				{
					$coll.wrapAll( $q );
				}
			});
		}, $quote);
	}
	function unquoteSelection()
	{
		this.manipulateSelection(function( range ){
			this.getRangeElements( range, 'blockquote > *' ).each(function(){
				var el		= this,
					$el		= $(el),
					$parent	= $el.closest('blockquote'),
					$bq		= $parent.clone().html(''),
					$sibs	= $parent.children(),
					last	= $sibs.length - 1,
					$coll	= $();

				$el.unwrap('blockquote');

				if ( last > 0 )
				{
					$sibs.each(function(i){
						if ( this != el )
						{
							$coll = $coll.add(this);
						}

						if ( i == last ||
							 this == el )
						{
							$coll.wrapAll($bq.clone());
							$coll = $();
						}
					});
				}

				$parent = $el.parent();
				if ( WysiHat.Element.isSubContainer( $parent ) &&
				 	 $parent.children().length == 1 )
				{
					$parent.html($el.html());
				}
			});
		});
	}
	function toggleIndentation()
	{
		if ( this.isIndented() )
		{
			this.unquoteSelection();
		}
		else
		{
			this.quoteSelection();
		}
	}
	function isIndented()
	{
		return selectionIsWithin('blockquote');
	}


	function fontSelection(font)
	{
		this.execCommand('fontname', FALSE, font);
	}
	function fontSizeSelection(fontSize)
	{
		this.execCommand('fontsize', FALSE, fontSize);
	}
	function colorSelection(color)
	{
		this.execCommand('forecolor', FALSE, color);
	}
	function backgroundColorSelection(color)
	{
		if ( $.browser.mozilla )
		{
			this.execCommand('hilitecolor', FALSE, color);
		}
		else
		{
			this.execCommand('backcolor', FALSE, color);
		}
	}


	function alignSelection(alignment)
	{
		this.execCommand('justify' + alignment);
	}
	function alignSelected()
	{
		var node = WIN.getSelection().getNode();
		return $(node).css('textAlign');
	}


	function linkSelection(url)
	{
		this.execCommand('createLink', FALSE, url);
	}
	function unlinkSelection()
	{
		this.manipulateSelection(function( range ){
			this.getRangeElements( range, '[href]' ).each(this.clearElement);
		});
	}
	function isLinked()
	{
		return selectionIsWithin('a[href]');
	}


	function toggleOrderedList()
	{
		var
		$list	= $('<ol/>');

		if ( isOrderedList() )
		{
			this.manipulateSelection(function( range, $list ){
				this.getRangeElements( range, 'ol' ).each(function(i){
					var $this = $(this);
					$this.children('li').each(function(){
						var $this = $(this);
						replaceElement( $this, 'p' );
						$this.find('ol,ul').each(function(){
							var	$parent = $(this).parent();
							if ( $parent.is('p') )
							{
								deleteElement.apply( $parent );
							}
						});
					});
					deleteElement.apply( $this );
				});
			});
		}
		else
		{
			this.manipulateSelection(function( range, $list ){
				var $l = $list.clone();
				this.getRangeElements( range, block_els ).each(function(i){
					var $this = $(this);
					if ( $this.parent().is('ul') )
					{
						replaceElement( $this.parent(), 'ol' );
						$l = $this.parent();
					}
					else
					{
						if ( ! i )
						{
							$this.replaceWith( $l );
						}
						$this.appendTo( $l );
					}
				});
				$l.children(':not(li)').each(function(){
					replaceElement( $(this), 'li' );
				});
			}, $list );
		}
		$(DOC.activeElement).trigger( CHANGE_EVT );
	}
	function insertOrderedList()
	{
		toggleOrderedList();
	}
	function isOrderedList()
	{
		return ( selectionIsWithin('ol') || document.queryCommandState('insertOrderedList') );
	}
	function toggleUnorderedList()
	{
		var
		$list	= $('<ul/>');

		if ( isUnorderedList() )
		{
			this.manipulateSelection(function( range, $list ){
				this.getRangeElements( range, 'ul' ).each(function(i){
					var $this = $(this);
					$this.children('li').each(function(){
						var $this = $(this);
						replaceElement( $this, 'p' );
						$this.find('ol,ul').each(function(){
							var	$parent = $(this).parent();
							if ( $parent.is('p') )
							{
								deleteElement.apply( $parent );
							}
						});
					});
					deleteElement.apply( $this );
				});
			});
		}
		else
		{
			this.manipulateSelection(function( range, $list ){
				var $l = $list.clone();
				this.getRangeElements( range, block_els ).each(function(i){
					var $this = $(this);
					if ( $this.parent().is('ol') )
					{
						replaceElement( $this.parent(), 'ul' );
						$l = $this.parent();
					}
					else
					{
						if ( ! i )
						{
							$this.replaceWith( $l );
						}
						$this.appendTo( $l );
					}
				});
				$l.children(':not(li)').each(function(){
					replaceElement( $(this), 'li' );
				});
			}, $list );
		}
		$(DOC.activeElement).trigger( CHANGE_EVT );
	}
	function insertUnorderedList()
	{
		toggleUnorderedList();
	}
	function isUnorderedList()
	{
		return ( selectionIsWithin('ul') || document.queryCommandState('insertUnorderedList') );
	}


	function insertImage( url, attrs )
	{
		this.execCommand('insertImage', FALSE, url);
	}


	function insertHTML(html)
	{
		if ( $.browser.msie )
		{
			var range = WIN.document.selection.createRange();
			range.pasteHTML(html);
			range.collapse(FALSE);
			range.select();
			$(DOC.activeElement).trigger( CHANGE_EVT );
		}
		else
		{
			this.execCommand('insertHTML', FALSE, html);
		}
	}

	function wrapHTML()
	{
		var
		selection	= WIN.getSelection(),
		range		= selection.getRangeAt(0),
		node		= selection.getNode(),
		arg_length	= arguments.length,
		el;

		if (range.collapsed)
		{
			range = DOC.createRange();
			range.selectNodeContents(node);
			selection.removeAllRanges();
			selection.addRange(range);
		}
		range = selection.getRangeAt(0);
		while ( arg_length-- )
		{
			el = $('<' + arguments[arg_length] + '/>');
			range.surroundContents( el.get(0) );
		}
		$(DOC.activeElement).trigger( CHANGE_EVT );
	}

	function changeContentBlock( tagName )
	{

		var
		selection	= WIN.getSelection(),
		editor		= this,
		$editor		= $(editor),
		replaced	= 'WysiHat-replaced',
		i			= selection.rangeCount,
		ranges		= [],
		range;

		while ( i-- )
		{
			range	= selection.getRangeAt( i );
			ranges.push( range );

			this.getRangeElements( range, block_els )
				.each(function(){
					editor.replaceElement( $(this), tagName );
				 })
				.data( replaced, TRUE );

		}
		$editor
			.children( tagName )
				.removeData( replaced );

		$(DOC.activeElement).trigger( CHANGE_EVT );

		this.restoreRanges( ranges );
	}

	function unformatContentBlock()
	{
		this.changeContentBlock('p');
	}

	function replaceElement( $el, tagName )
	{
		if ( $el.is( '.' + WYSIHAT_EDITOR ) )
		{
			return;
		}

		var
		old		= $el.get(0),
		$new	= $('<'+tagName+'/>')
					.html( $el.html() ),

		attrs	= old.attributes,
		len		= attrs.length;
		if ( len )
		{
			while ( len-- )
			{
				$new.attr( attrs[len].name, attrs[len].value );
			}
		}

		$el.replaceWith( $new );

		$(DOC.activeElement).trigger( CHANGE_EVT );

		return $new;
	}

	function deleteElement()
	{
		var $this = $(this);
		$this.replaceWith( $this.html() );

		$(DOC.activeElement).trigger( CHANGE_EVT );
	}

	function stripFormattingElements()
	{

		function stripFormatters( i, el )
		{
			var $el = $(el);

			$el.children().each(stripFormatters);

			if ( isFormatter( $el ) )
			{
				deleteElement.apply( $el );
			}
		}

		var
		selection	= WIN.getSelection(),
		isFormatter	= WysiHat.Element.isFormatter,
		i			= selection.rangeCount,
		ranges		= [],
		range;

		while ( i-- )
		{
			range = selection.getRangeAt( i );
			ranges.push( range );
			this.getRangeElements( range, block_els ).each( stripFormatters );
		}

		$(DOC.activeElement).trigger( CHANGE_EVT );

		this.restoreRanges( ranges );
	}

	function isValidCommand( cmd )
	{
		return ( $.inArray( cmd, valid_cmds ) > -1 );
	}
	function getDefaultShortcut( cmd )
	{
		return ( !! dflt_shortcuts[cmd] ) ? dflt_shortcuts[cmd] : false;
	}
	function execCommand( command, ui, value )
	{
		var handler = this.commands[command];
		if ( handler )
		{
			handler.bind(this)(value);
		}
		else
		{
			noSpans();
			try {
				WIN.document.execCommand(command, ui, value);
			}
			catch(e) { return NULL; }
		}
		$(DOC.activeElement).trigger( CHANGE_EVT );
	}
	function noSpans()
	{
		try {
			WIN.document.execCommand('styleWithCSS', 0, FALSE);
			noSpans = function(){
				WIN.document.execCommand('styleWithCSS', 0, FALSE);
			};
		} catch (e) {
			try {
		    	WIN.document.execCommand('useCSS', 0, TRUE);
				noSpans = function(){
			    	WIN.document.execCommand('useCSS', 0, TRUE);
				};
			} catch (e) {
				try {
					WIN.document.execCommand('styleWithCSS', FALSE, FALSE);
					noSpans = function(){
						WIN.document.execCommand('styleWithCSS', FALSE, FALSE);
					};
				}
		        catch (e) {}
			}
		}
	}
	noSpans();
	function queryCommandState(state)
	{
		var handler = this.queryCommands[state];
		if ( handler )
		{
			return handler();
		}
		else
		{
			try {
				return WIN.document.queryCommandState(state);
			}
			catch(e) { return NULL; }
		}
	}

	function getSelectedStyles()
	{
		var
		styles = {},
		editor = this;
		editor.styleSelectors.each(function(style){
			var node = editor.selection.getNode();
			styles[style.first()] = $(node).css(style.last());
		});
		return styles;
	}

	function toggleHTML( e )
	{
		var
		HTML	= FALSE,
		$editor	= $(this),
		$target	= $( e.target ),
		text	= $target.text(),
		$btn	= $target.closest( 'button,[role=button]' ),
		$field	= $editor.data('field'),
		$tools	= $btn.siblings();

		if ( $btn.data('toggle-text') == UNDEFINED )
		{
			$btn.data('toggle-text','View Content');
		}

		this.toggleHTML = function()
		{
			if ( ! HTML )
			{
				$btn.find('b').text($btn.data('toggle-text'));
				$tools.hide();
				$editor.trigger('WysiHat-editor:change:immediate').hide();
				$field.show();
			}
			else
			{
				$btn.find('b').text(text);
				$tools.show();
				$field.trigger('WysiHat-field:change:immediate').hide();
				$editor.show();
			}
			HTML = ! HTML;
		};

		this.toggleHTML();
	}


	function manipulateSelection()
	{
		var
		selection	= WIN.getSelection(),
		i			= selection.rangeCount,
		ranges		= [],
		args		= arguments,
		callback	= args[0],
		range;

		while ( i-- )
		{
			range	= selection.getRangeAt( i );
			ranges.push( range );

			args[0] = range;

			callback.apply( this, args );
		}
		$(DOC.activeElement).trigger( CHANGE_EVT );
		this.restoreRanges( ranges );
	}
	function getRangeElements( range, tagNames )
	{
		var
		$from	= $( range.startContainer ).closest( tagNames ),
		$to		= $( range.endContainer ).closest( tagNames ),
		$els	= $('nullset');

		if ( !! $from.parents('.WysiHat-editor').length &&
		 	 !! $to.parents('.WysiHat-editor').length )
		{
			$els = $from;

			if ( ! $from.filter( $to ).length )
			{
				if ( $from.nextAll().filter( $to ).length )
				{
					$els = $from.nextUntil( $to ).andSelf().add( $to );
				}
				else
				{
					$els = $from.prevUntil( $to ).andSelf().add( $to );
				}
			}

		}

		return $els;
	}
	function getRanges()
	{
		var
		selection	= WIN.getSelection(),
		i			= selection.rangeCount,
		ranges		= [],
		range;

		while ( i-- )
		{
			range	= selection.getRangeAt( i );
			ranges.push( range );
		}

		return ranges;
	}
	function restoreRanges( ranges )
	{
		var
		selection = WIN.getSelection(),
		i = ranges.length;

		selection.removeAllRanges();
		while ( i-- )
		{
			selection.addRange( ranges[i] );
		}
	}
	function selectionIsWithin( tagNames )
	{
		var
		phrases	= WysiHat.Element.getPhraseElements(),
		phrase	= FALSE,
		tags	= tagNames.split(','),
		t		= tags.length,
		sel		= WIN.getSelection(),
		a		= sel.anchorNode,
		b		= sel.focusNode;

		if ( a.nodeType &&
			 a.nodeType == 3 &&
			 a.nodeValue == '' )
		{
			a = a.nextSibling;
		}

		if ( $.browser.mozilla )
		{
			while ( t-- )
			{
				if ( $.inArray( tags[t], phrases ) != -1 )
				{
					phrase = TRUE;
					break;
				}
			}
			if ( phrase &&
				 a.nodeType == 1 &&
				 $.inArray( a.nodeName.toLowerCase(), phrases ) == -1 )
			{
				t = a.firstChild;
				if ( t )
				{
					if ( t.nodeValue == '' )
					{
						t = t.nextSibling;
					}
					if ( t.nodeType == 1 )
					{
						a = t;
					}
				}
			}
		}

		while ( a.nodeType != 1 &&
			 	b.nodeType != 1 )
		{
			if ( a.nodeType != 1 )
			{
				a = a.parentNode;
			}
			if ( b.nodeType != 1 )
			{
				b = b.parentNode;
			}
		}
		return !! ( $(a).closest( tagNames ).length ||
		 			$(b).closest( tagNames ).length );
	}


	return {
		boldSelection:				boldSelection,
		isBold:						isBold,
		italicizeSelection:			italicizeSelection,
		isItalic:					isItalic,
		underlineSelection:			underlineSelection,
		isUnderlined:				isUnderlined,
		strikethroughSelection:		strikethroughSelection,
		isStruckthrough:			isStruckthrough,

		quoteSelection:				quoteSelection,
		unquoteSelection:			unquoteSelection,
		toggleIndentation:			toggleIndentation,
		isIndented:					isIndented,

		fontSelection:				fontSelection,
		fontSizeSelection:			fontSizeSelection,
		colorSelection:				colorSelection,
		backgroundColorSelection:	backgroundColorSelection,

		alignSelection:				alignSelection,
		alignSelected:				alignSelected,

		linkSelection:				linkSelection,
		unlinkSelection:			unlinkSelection,
		isLinked:					isLinked,

		toggleOrderedList:			toggleOrderedList,
		insertOrderedList:			insertOrderedList,
		isOrderedList:				isOrderedList,
		toggleUnorderedList:		toggleUnorderedList,
		insertUnorderedList:		insertUnorderedList,
		isUnorderedList:			isUnorderedList,

		insertImage:				insertImage,

		insertHTML:					insertHTML,
		wrapHTML:					wrapHTML,

		changeContentBlock:			changeContentBlock,
		unformatContentBlock:		unformatContentBlock,
		replaceElement:				replaceElement,
		deleteElement:				deleteElement,
		stripFormattingElements:	stripFormattingElements,

		execCommand:				execCommand,
		noSpans:					noSpans,
		queryCommandState:			queryCommandState,
		getSelectedStyles:			getSelectedStyles,

		toggleHTML:					toggleHTML,

		isValidCommand:				isValidCommand,
		getDefaultShortcut:			getDefaultShortcut,
		manipulateSelection:		manipulateSelection,
		getRangeElements:			getRangeElements,
		getRanges:					getRanges,
		restoreRanges:				restoreRanges,
		selectionIsWithin:			selectionIsWithin,

		commands: {},

		queryCommands: {
			bold:			isBold,
			italic:			isItalic,
			underline:		isUnderlined,
			strikethrough:	isStruckthrough,
			createLink:		isLinked,
			orderedlist:	isOrderedList,
			unorderedlist:	isUnorderedList
		},

		styleSelectors: {
			fontname:		'fontFamily',
			fontsize:		'fontSize',
			forecolor:		'color',
			hilitecolor:	'backgroundColor',
			backcolor:		'backgroundColor'
		}
	};
})( window, document, jQuery );
if ( typeof Node == "undefined" )
{
	(function(){
		function Node(){
			return {
				ATTRIBUTE_NODE: 2,
				CDATA_SECTION_NODE: 4,
				COMMENT_NODE: 8,
				DOCUMENT_FRAGMENT_NODE: 11,
				DOCUMENT_NODE: 9,
				DOCUMENT_TYPE_NODE: 10,
				ELEMENT_NODE: 1,
				ENTITY_NODE: 6,
				ENTITY_REFERENCE_NODE: 5,
				NOTATION_NODE: 12,
				PROCESSING_INSTRUCTION_NODE: 7,
				TEXT_NODE: 3
			};
		};
		window.Node = new Node();
	})();
}

(function( DOC, $ ){

	if ( $.browser.msie )
	{

		function setBookmark()
		{
			var
			$bookmark	= $('#WysiHat-bookmark'),
			$parent		= $('<div/>'),
			range		= this._document.selection.createRange();

			if ( $bookmark.length > 0 )
			{
				$bookmark.remove();
			}

			$bookmark = $( '<span id="WysiHat-bookmark">&nbsp;</span>' )
							.appendTo( $parent );

			range.collapse(true);
			range.pasteHTML( $parent.html() );
		}

		function moveToBookmark(element)
		{
			var
			$bookmark	= $('#WysiHat-bookmark'),
			range		= this._document.selection.createRange();

			if ( $bookmark.length > 0 )
			{
				$bookmark.remove();
			}

			range.moveToElementText( $bookmark.get(0) );
			range.collapse(true);
			range.select();

			$bookmark.remove();
		}

	}
	else
	{

		function setBookmark()
		{
			var $bookmark	= $('#WysiHat-bookmark');

			if ( $bookmark.length > 0 )
			{
				$bookmark.remove();
			}

			$bookmark = $( '<span id="WysiHat-bookmark">&nbsp;</span>' );

			this.getRangeAt(0).insertNode( $bookmark.get(0) );
		}

		function moveToBookmark(element)
		{
			var
			$bookmark	= $('#WysiHat-bookmark'),
			range		= DOC.createRange();

			if ( $bookmark.length > 0 )
			{
				$bookmark.remove();
			}

			range.setStartBefore( $bookmark.get(0) );
			this.removeAllRanges();
			this.addRange(range);

			$bookmark.remove();
		}

	}

	$.extend( Selection.prototype, {
		setBookmark:    setBookmark,
		moveToBookmark: moveToBookmark
	});

})(document,jQuery);
(function($){

	var
	tagsToRemove	= {},
	tagsToAllow		= {},
	tagsToSkip		= {};

	function cloneWithAllowedAttributes( $el, allowedAttributes )
	{
		var
		tagName	= $el.get(0).tagName.toLowerCase(),
		length	= allowedAttributes.length,
		$copy	= $('<' + tagName + '></' + tagName + '>');

		while ( length-- )
		{
			attribute = allowedAttributes[i];
			if ( $el.attr(attribute) )
			{
				$copy.attr( attribute, $el.attr(attribute) );
			}
		}

		return result;
	}

	function withEachChildNodeOf( $el, callback )
	{
		$el.children().map(callback);
	}

	function sanitizeNode( $node )
	{
		var
		node	= $node.get(0),
		tagName, $newNode;

		switch ( node.nodeType )
		{
			case '1':
				tagName = node.tagName.toLowerCase();
				if ( tagsToSkip )
				{
					$newNode = $node.clone(false);
					sanitizeChildren( $node, $newNode );
					$node.before($newNode);
				}
				else if ( tagName in tagsToAllow )
				{
					$newNode = cloneWithAllowedAttributes( $node, tagsToAllow[tagName] );
					sanitizeChildren( $node, $newNode );
					$node.before($newNode);
				}
				else if ( ! ( tagName in tagsToRemove ) )
				{
					sanitizeChildren( $node );
				}
				break;
			case '8':
				$node.remove();
				break;
		}
	}

	function sanitizeChildren( $node, $newNode )
	{
		withEachChildNodeOf( $node, function(){
			var $child = $(this);
			if ( $newNode )
			{
				$newNode.append( $child );
			}
			else
			{
				$node.before( $child );
			}
			sanitizeNode( $child );
		});
	}

	$.fn.sanitizeContents = function(options)
	{
		options			= options || { remove: '', allow: '', skip: [] };
		tagsToRemove	= {};
		tagsToAllow		= {};
		tagsToSkip		= options.skip;

		var
		$element = $(this),

		tags =  ( options.remove || '' ).split( ',' );

		if ( tags.length > 0 &&
			 tags[0] != '' )
		{
			$.each( tags, function(tagName){
				tagsToRemove[$.trim(tagName)] = true;
			});
		}

		tags =  ( options.allow || '' ).split( ',' );
		if ( tags.length > 0 &&
			 tags[0] != '' )
		{
			$.each( tags, function(selector){
				var
				parts				= $.trim(selector).split( /[\[\]]/ ),
				tagName 			= parts[0],
				allowedAttributes	= $.grep( parts.slice(1), function( n, i ){
					return ( /./ ).test(n);
				});
				tagsToAllow[tagName] = allowedAttributes;
			});
		}

		withEachChildNodeOf( $element, function(){
			sanitizeNode( $(this) );
		});

		return $element;
	};

})(jQuery);
(function() {
  function onReadyStateComplete(document, callback) {

    function checkReadyState() {
      if (document.readyState === 'complete') {
        $(document).unbind('readystatechange', checkReadyState);
        callback();
        return true;
      } else {
        return false;
      }
    }

    $(document).bind('readystatechange', checkReadyState);
    checkReadyState();
  }

  function observeFrameContentLoaded(element) {
    element = $(element);
    var bare = element.get(0);

    var loaded, contentLoadedHandler;

    loaded = false;
    function fireFrameLoaded() {
      if (loaded) { return };

      loaded = true;
      if (contentLoadedHandler) { contentLoadedHandler.stop(); }
      element.trigger('frame:loaded');
    }

    if (window.addEventListener) {
      contentLoadedHandler = $(document).bind("DOMFrameContentLoaded", function(event) {
        if (element == $(this))
          fireFrameLoaded();
      });
    }

    element.load(function() {
      var frameDocument;
      if (typeof element.contentDocument !== 'undefined') {
        frameDocument = element.contentDocument;
      } else if (typeof element.contentWindow !== 'undefined' && typeof element.contentWindow.document !== 'undefined') {
        frameDocument = element.contentWindow.document;
      }

      onReadyStateComplete(frameDocument, fireFrameLoaded);
    });

    return element;
  }

  function onFrameLoaded($element, callback) {
    $element.bind('frame:loaded', callback);
    $element.observeFrameContentLoaded();
  }

  jQuery.fn.observeFrameContentLoaded = observeFrameContentLoaded;
  jQuery.fn.onFrameLoaded = onFrameLoaded;
})();
jQuery(document).ready(function(){

	var
	$		= jQuery,
	DOC		= document,
	$doc	= $(DOC),
	previousRange,
	selectionChangeHandler;

	if ( 'onselectionchange' in DOC &&
		 'selection' in DOC )
	{
		selectionChangeHandler = function()
		{
			var
			range	= DOC.selection.createRange(),
			element	= range.parentElement();
			$(element)
				.trigger( 'WysiHat-selection:change' );
		}

 		$doc.bind( 'selectionchange', selectionChangeHandler );
	}
	else
	{
		selectionChangeHandler = function()
		{
			var
			element        = DOC.activeElement,
			elementTagName = element.tagName.toLowerCase(),
			selection, range;

			if ( elementTagName == 'textarea' ||
				 elementTagName == 'input' )
			{
				previousRange = null;
			}
			else
			{
				selection = window.getSelection();
				if (selection.rangeCount < 1) { return };

				range = selection.getRangeAt(0);
				if ( range && range.equalRange(previousRange) ) { return; }

				previousRange	= range;
				element			= range.commonAncestorContainer;
				while (element.nodeType == Node.TEXT_NODE)
				{
					element = element.parentNode;
				}
			}

			$(element)
				.trigger( 'WysiHat-selection:change' );
		};

		$doc.mouseup( selectionChangeHandler );
		$doc.keyup( selectionChangeHandler );
	}

});
(function($){

	if ( ! $.browser.msie )
	{
		$('body')
			.delegate('.WysiHat-editor', 'contextmenu click doubleclick keypress', function(){

				var
				$editor		= $(this),
				$field		= $editor.data('field'),
				selection	= window.getSelection(),
				range		= selection.getRangeAt(0);

				if ( range )
				{
					range = range.cloneRange();
				}
				else
				{
					range = document.createRange();
					range.selectNode( $editor.get(0).firstChild );
				}

				$field.data(
					'saved-range',
					{
						startContainer:	range.startContainer,
						startOffset:	range.startOffset,
						endContainer: 	range.endContainer,
						endOffset:		range.endOffset
					}
				);
			 })
			.delegate('.WysiHat-editor', 'paste', function(e){
				var
				original_event	= e.originalEvent,
				$editor			= $(this),
				$field			= $editor.data('field');

				$field.data( 'original-html', $editor.children().detach() );

			    if ( original_event.clipboardData &&
					 original_event.clipboardData.getData )
				{
			        if ( /text\/html/.test( original_event.clipboardData.types ) )
					{
			            $editor.html( original_event.clipboardData.getData('text/html') );
			        }
			        else if ( /text\/plain/.test( original_event.clipboardData.types ) )
					{
			            $editor.html( original_event.clipboardData.getData('text/plain') );
			        }
			        else
					{
			            $editor.html('');
			        }
			        waitforpastedata( $editor );
			        original_event.stopPropagation();
			        original_event.preventDefault();
			        return false;
			    }
			    else
				{
			        $editor.html('');
			        waitforpastedata( $editor );
			        return true;
			    }
			 });

			function waitforpastedata( $editor )
			{
				if ( $editor.contents().length )
				{
					processpaste( $editor );
			    }
			    else
				{
					setTimeout(function(){
						waitforpastedata( $editor );
					}, 20 );
			    }
			}

			function processpaste( $editor )
			{
				$editor
					.remove('script,noscript,style,:hidden')
					.html( $editor.get(0).innerHTML.replace( /></g, '> <') );

				var
				$field			= $editor.data('field'),
				$original_html	= $field.data('original-html'),
				pasted_content	= document.createTextNode( $editor.text() ),
				saved_range		= $field.data('saved-range'),
				range			= document.createRange();

				$editor
					.empty()
					.append( $original_html );

				range.setStart( saved_range.startContainer, saved_range.startOffset );
				range.setEnd( saved_range.endContainer, saved_range.endOffset );

				if ( ! range.collapsed )
				{
					range.deleteContents();
				}

				range.insertNode( pasted_content );

				WysiHat.Formatting.cleanup( $editor );

				$editor.trigger( 'WysiHat-editor:change' );
			}
		}
		else
		{
			$('body')
				.delegate('.WysiHat-editor', 'paste', function(){
					WysiHat.Formatting.cleanup( $(this) );

					$editor.trigger( 'WysiHat-editor:change' );
				 });
		}

})(jQuery);

WysiHat.Formatting = (function($){

	var
	ACCUMULATING_LINE	   = {},
	EXPECTING_LIST_ITEM	   = {},
	ACCUMULATING_LIST_ITEM = {};

	return {
		cleanup: function( $element )
		{
			var
			replaceElement = WysiHat.Commands.replaceElement;
			$element
				.find('span')
					.each(function(){
						var $this = $(this);
						if ( $this.is('.Apple-style-span') )
						{
							$this.removeClass('.Apple-style-span');
						}
						if ( $this.css('font-weight') == 'bold' &&
						 	 $this.css('font-style') == 'italic' )
						{
							$this.removeAttr('style').wrap('<strong>');
							replaceElement( $this, 'em' );
						}
						else if ( $this.css('font-weight') == 'bold' )
						{
							replaceElement( $this.removeAttr('style'), 'strong' );
						}
						else if ( $this.css('font-style') == 'italic' )
						{
							replaceElement( $this.removeAttr('style'), 'em' );
						}
					 })
					.end()
				.children('div')
					.each(function(){
					 	var $this = $(this);
					 	if ( ! $this.get(0).attributes.length )
					 	{
					 		replaceElement( $this, 'p' );
					 	}
					 })
					.end()
				.find('b')
					.each(function(){
					 	replaceElement($(this),'strong');
					 })
					.end()
				.find('i')
					.each(function(){
					 	replaceElement($(this),'em');
					 })
					.end()
				.find('strike')
					.each(function(){
					 	replaceElement($(this),'del');
					 })
					.end()
				.find('u')
					.each(function(){
					 	replaceElement($(this),'ins');
					 })
					.end()
				.find('p:empty')
					.remove();
		},
		format: function( $el )
		{
			var
			re_blocks = new RegExp( '(<(?:ul|ol)>|<\/(?:' + WysiHat.Element.getBlocks().join('|') + ')>)[\r\n]*', 'g' ),
			html = $el.html()
						.replace('<p>&nbsp;</p>','')
						.replace(/<br\/?><\/p>/,'</p>')
						.replace( re_blocks,'$1\n' )
						.replace(/\n+/,'\n')
						.replace(/<p>\n+<\/p>/,'');
			$el.html( html );
		},
		getBrowserMarkupFrom: function( $el )
		{

			var $container = $('<div>' + $el.val().replace(/\n/,'') + '</div>');

			this.cleanup( $container );


			if ( $container.html() == '' ||
			 	 $container.html() == '<br>' ||
			 	 $container.html() == '<br/>' )
			{
				$container.html('<p>&nbsp;</p>');
			}

			return $container.html();

		},

		getApplicationMarkupFrom: function( $el )
		{
			var
			$clone			= $el.clone(),
			el_id			= $el.attr('id'),
			replaceElement	= WysiHat.Commands.replaceElement,
			$container, html;


			$container = $('<div/>').html($clone.html());

			if ( $container.html() == '' ||
			 	 $container.html() == '<br>' ||
			 	 $container.html() == '<br/>' )
			{
				$container.html('<p>&nbsp;</p>');
			}

			this.cleanup( $container );


			this.format( $container );

			return $container
					.html()
					.replace( /<\/?[A-Z]+/g, function(tag){
						return tag.toLowerCase();
					 });
		}

	};

})(jQuery);

(function($){

	WysiHat.Toolbar = function()
	{
		var
		$editor,
		$toolbar;

		function initialize( $el )
		{
			$editor	= $el;
			createToolbarElement();

		}

		function createToolbarElement()
		{
			$toolbar = $('<div class="' + WysiHat.name + '-editor-toolbar" role="presentation"></div>')
							.insertBefore( $editor );
		}

		function addButtonSet(options)
		{
			$(options.buttons).each(function(index, button){
				addButton(button);
			});
		}

		function addButton( options, handler )
		{
			var name, $button, shortcut;

			if ( ! options['name'] )
			{
				options['name'] = options['label'].toLowerCase();
			}
			name = options['name'];

			$button = createButtonElement( $toolbar, options );

			if ( handler )
			{
				options['handler'] = handler;
			}
			handler = buttonHandler( name, options );
			observeButtonClick( $button, handler );


			handler = buttonStateHandler( name, options );
			observeStateChanges( $button, name, handler );

			return $button;
		}

		function createButtonElement( $toolbar, options )
		{
			var $btn = $('<button aria-pressed="false" tabindex="-1"><b>' + options['label'] + '</b></button>')
							.addClass( 'button ' + options['name'] )
							.appendTo( $toolbar )
							.hover(
								function(){
									var $button = $(this).closest('button');
									$button.attr('title',$button.find('b').text());
								},
								function(){
									$(this).closest('button').removeAttr('title');
								}
							 );

			if ( options['cssClass'] )
			{
				$btn.addClass( options['cssClass'] );
			}

			if ( options['title'] )
			{
				$btn.attr('title',options['title']);
			}

			$btn.data( 'text', options['label'] );
			if ( options['toggle-text'] )
			{
				$btn.data( 'toggle-text', options['toggle-text'] );
			}

			return $btn;

		}

		function buttonHandler( name, options )
		{
			var handler = function(){};
			if ( options['handler'] )
			{
				handler = options['handler'];
			}
			else if ( WysiHat.Commands.isValidCommand( name ) )
			{
				handler = function( $editor )
				{
					return $editor.execCommand(name);
				};
			}
			return handler;
		}

		function observeButtonClick( $button, handler )
		{
			$button.click(function(e){
				handler( $editor, e );
				$editor.trigger( 'WysiHat-selection:change' );
				return false;
			});
		}

		function buttonStateHandler( name, options )
		{
			var handler = function(){};
			if ( options['query'] )
			{
				handler = options['query'];
			}
			else if ( WysiHat.Commands.isValidCommand( name ) )
			{
				handler = function( $editor )
				{
					return $editor.queryCommandState(name);
				};
			}
			return handler;
		}

		function observeStateChanges( $button, name, handler )
		{
			var previousState;
			$editor.bind( 'WysiHat-selection:change', function(){
				var state = handler( $editor, $button );
				if (state != previousState)
				{
					previousState = state;
					updateButtonState( $button, name, state );
				}
			});
		}

		function updateButtonState( $button, name, state )
		{
			var
			text	= $button.data('text'),
			toggle	= $button.data('toggle-text');

			if ( state )
			{
				$button
					.addClass('selected')
					.attr('aria-pressed','true')
					.find('b')
						.text( toggle ? toggle : text );
			}
			else
			{
				$button
					.removeClass('selected')
					.attr('aria-pressed','false')
					.find('b')
						.text( text );
			}
		}

		function shortcutTest( name, options )
		{
			var shortcut = options['shortcut'] || WysiHat.Commands.getDefaultShortcut( name ),
			alt, ctrl, code;
			if ( !! shortcut )
			{
				alt		= !! shortcut['alt'];
				ctrl	= !! shortcut['ctrl'];
				code	= shortcut['keycode'];
				shortcut = function( e ){
					return ( code == e.which &&
							 alt == e.altKey &&
							 ctrl == e.ctrlKey );
				};
			}
			return shortcut;
		}

		function observeShortcut( test, handler )
		{
			$editor.keydown(function( e ){
				if ( test( e ) )
				{
					handler();
				}
			});
		}

		function buttonKey( e )
		{
			var
			$this	= $(this).closest( 'button,[role=button]' ),
			key		= e.which,
			$next;
			switch ( key )
			{
				case 37:
				case 38:
					$next = $this.prev();
					if ( ! $next.length )
					{
						$next = $( $this.parent().get(0).lastChild );
					}
					$next.focus();
					break;
				case 39:
				case 40:
					$next = $this.next();
					if ( ! $next.length )
					{
						$next = $( $this.parent().get(0).firstChild );
					}
					$next.focus();
					break;
			}
		}

		return {
			initialize:           initialize,
			createToolbarElement: createToolbarElement,
			addButtonSet:         addButtonSet,
			addButton:            addButton,
			createButtonElement:  createButtonElement,
			buttonHandler:        buttonHandler,
			observeButtonClick:   observeButtonClick,
			buttonStateHandler:   buttonStateHandler,
			observeStateChanges:  observeStateChanges,
			updateButtonState:    updateButtonState
		};
	};

})(jQuery);


WysiHat.Toolbar.ButtonSets = {};

WysiHat.Toolbar.ButtonSets.Basic = [
	{ label: "Bold" },
	{ label: "Underline" },
	{ label: "Italic" }
];

WysiHat.Toolbar.ButtonSets.Standard = [
	{ label: "Bold", cssClass: 'toolbar_button' },
	{ label: "Italic", cssClass: 'toolbar_button' },
	{ label: "Strikethrough", cssClass: 'toolbar_button' },
	{ label: "Bullets", cssClass: 'toolbar_button', handler: function(editor) { return editor.toggleUnorderedList(); } }
];
jQuery.fn.wysihat = function(options) {
	options = jQuery.extend({
		buttons: WysiHat.Toolbar.ButtonSets.Standard
	}, options);

	return this.each(function(){
		var
		editor	= WysiHat.Editor.attach( jQuery(this) ),
		toolbar	= new WysiHat.Toolbar(editor);
		toolbar.initialize(editor);
		toolbar.addButtonSet(options);
	});
};
