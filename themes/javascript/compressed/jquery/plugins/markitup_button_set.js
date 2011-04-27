/*!
 * markItUp!
 * ----------------------------------------------------------------------------
 * Copyright (C) 2008 Jay Salvat
 * http://markitup.jaysalvat.com/
 */

mySettings={nameSpace:"html",onShiftEnter:{keepDefault:!1,replaceWith:"<br />\n"},onCtrlEnter:{keepDefault:!1,openWith:"\n<p>",closeWith:"</p>\n"},onTab:{keepDefault:!1,openWith:"     "},markupSet:[{name:"Bold",key:"B",openWith:"<strong>",closeWith:"</strong>"},{name:"Italic",key:"I",openWith:"<em>",closeWith:"</em>"},{name:"Stroke through",key:"S",openWith:"<del>",closeWith:"</del>"},{name:"Insert",key:"",openWith:"<ins>",closeWith:"</ins>"},{name:"Ul",openWith:"<ul>\n",closeWith:"</ul>\n"},{name:"Ol",
openWith:"<ol>\n",closeWith:"</ol>\n"},{name:"Li",openWith:"<li>",closeWith:"</li>"},{name:"Paragraph",openWith:'<p(!( class="[![Class]!]")!)>',closeWith:"</p>"},{name:"Heading 1",key:"1",openWith:'<h1(!( class="[![Class]!]")!)>',closeWith:"</h1>",placeHolder:"Your title here..."},{name:"Heading 2",key:"2",openWith:'<h2(!( class="[![Class]!]")!)>',closeWith:"</h2>",placeHolder:"Your title here..."},{name:"Heading 3",key:"3",openWith:'<h3(!( class="[![Class]!]")!)>',closeWith:"</h3>",placeHolder:"Your title here..."},
{name:"Heading 4",key:"4",openWith:'<h4(!( class="[![Class]!]")!)>',closeWith:"</h4>",placeHolder:"Your title here..."},{name:"Heading 5",key:"5",openWith:'<h5(!( class="[![Class]!]")!)>',closeWith:"</h5>",placeHolder:"Your title here..."},{name:"Heading 6",key:"6",openWith:'<h6(!( class="[![Class]!]")!)>',closeWith:"</h6>",placeHolder:"Your title here..."},{name:"Link",key:"L",openWith:'<a href="[![Link:!:http://]!]"(!( title="[![Title]!]")!)>',closeWith:"</a>",placeHolder:"Your text to link..."},
{name:"Picture",key:"P",replaceWith:""},{name:"+",key:"",replaceWith:""}]};
