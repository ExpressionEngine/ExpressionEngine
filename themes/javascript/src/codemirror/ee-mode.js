/**
 * An EE textmirror "mode". Basically a lexer.
 */
(function(CodeMirror) {

	"use strict";

	CodeMirror.defineMode("ee:inner", function() {
		var comment, condition, tag;

		function tokenBase(stream, state) {
			stream.eatWhile(/[^\{]/);

			if (comment = stream.match(/^\{!--/, false)) {
				state.tokenize = inComment();
				return 'tag';
			}
			else if (condition = stream.match(/^\{(if|if:elseif)\s/, false)) {
				state.tokenize = inCondition();
				return 'tag';
			}
			else if (condition = stream.match(/\{(if:else|\/if)\}/, false)) {
				state.tokenize = inConditionalKeyword();
				return 'punctuation';
			}
			else if (tag = stream.match(/\{\/?([\w:]+)/, false)) {
				state.tokenize = inEETag(tag[1]);
				return 'punctuation';
			}

			stream.next();
		}

		function inConditionalKeyword() {

			return function(stream, state) {
				if (stream.match(/(if:else|\/if)/)) {
					return 'keyword';
				}

				var ch = stream.next();

				if (ch == '{') {
					return 'punctuation';
				}

				if (ch == '}') {
					stream.next();
					state.tokenize = tokenBase;
					return 'punctuation';
				}

				return 'punctuation';
			};
		}

		function inEETag(tagname) {

			return function(stream, state) {
				stream.eatWhile(/\s+/);

				if (stream.match(/"(\\|\"|[^"])*"/)) {
					return 'string';
				}

				if (stream.match(/'(\\|\'|[^'])*'/)) {
					return 'string';
				}

				var variable;

				if (variable = stream.match(/\w*([a-zA-Z]([\w:-]+\w)?|(\w[\w:-]+)?[a-zA-Z])\w*/)) {
					return variable[0] == tagname ? 'variable' : 'variable-2';
				}

				var ch = stream.next();

				if (ch == '=' || ch == '{') {
					return 'punctuation';
				}

				if (ch == '}') {
					stream.next();
					state.tokenize = tokenBase;
					return 'punctuation';
				}

				return 'punctuation';
			};
		}

		function inCondition() {

			return function(stream, state) {

				stream.eatWhile(/\s+/);

				if (stream.match(/(if|if:elseif)/)) {
					return 'keyword';
				}

				if (stream.match(/\b(true|false)\b/i)) {
					return 'keyword';
				}

				if (stream.match(/\b(and|or|xor)\b/i)) {
					return 'operator';
				}

				if (stream.match(/"(\\|\"|[^"])*"/)) {
					return 'string';
				}

				if (stream.match(/'(\\|\'|[^'])*'/)) {
					return 'string';
				}

				if (stream.match(/\b(\d+\.\d*|\d*\.\d+|\d+)\b/)) {
					return 'number';
				}

				if (stream.match(/\w*([a-zA-Z]([\w:-]+\w)?|(\w[\w:-]+)?[a-zA-Z])\w*/)) {
					return 'variable';
				}

				if (stream.match(/[=!|<>!&%~\(\)\$\^\*\+\-\.]+/)) {
					return 'operator';
				}

				var ch = stream.next();

				if (ch == '{' || ch == '/') {
					return 'punctuation';
				}


				if (ch == '}') {
					stream.next();
					state.tokenize = tokenBase;
					return 'punctuation';
				}

				return 'punctuation';
			};
		}

		function inComment() {

			return function(stream, state) {
				stream.eat(/\{!--/);
				stream.next();
				if (stream.match(/^--}/, true)) {
					state.tokenize = tokenBase;
				}

				return 'comment';
			};
		}

		return {
			startState: function () {
				return {tokenize: tokenBase};
			},

			token: function (stream, state) {
				return state.tokenize(stream, state);
			}
		};
	});

	// lay ee on top of the html mode
	CodeMirror.defineMode("ee", function(config) {
		var htmlBase = CodeMirror.getMode(config, "text/html");
		var eeInner = CodeMirror.getMode(config, "ee:inner");
		return CodeMirror.overlayMode(htmlBase, eeInner);
	});

	CodeMirror.defineMIME("text/x-ee", "ee");

})(CodeMirror);