"use strict";

/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * EE codemirror linter.
 */
(function () {
  "use strict";

  function tagError(tagname) {
    var addon_exists = jQuery.inArray(tagname, EE.editor.lint.available) >= 0,
        addon_not_installed = jQuery.inArray(tagname, EE.editor.lint.not_installed) >= 0;

    if (!addon_exists || addon_not_installed) {
      if (!addon_exists) {
        return 'Add-on "' + tagname + '" does not exist.';
      }

      return 'Module "' + tagname + '" exists, but is not installed.';
    }

    return '';
  }

  EE.codemirror_linter = {
    getAnnotations: function getAnnotations(text) {
      var found = [],
          regex = /(\{\/?exp:)([\w]+)/i,
          skipped_lines = 0,
          last_ch = 0,
          tag;

      while (tag = regex.exec(text)) {
        // find the line and character of the match
        var skipped_text = text.substr(0, tag.index),
            lines = skipped_text.split("\n"),
            line = lines.length - 1,
            ch = lines[line].length + tag[1].length; // adjust line to absolute position in the textarea

        line += skipped_lines;
        skipped_lines = line; // adjust character for same-line tags

        if (lines.length == 1) {
          ch += last_ch;
        } // check tag for validity


        var error = tagError(tag[2]);

        if (error) {
          found.push({
            from: CodeMirror.Pos(line, ch),
            to: CodeMirror.Pos(line, ch + tag[2].length),
            message: error
          });
        } // store ch for next search


        last_ch = ch + tag[2].length; // trim text for next search

        text = text.substr(tag.index + tag[0].length);
      }

      return found;
    }
  };
})();
/**
 * An EE textmirror "mode". Basically a lexer.
 */


(function (CodeMirror) {
  "use strict";

  CodeMirror.defineMode("ee:inner", function () {
    var comment, condition, tag;

    function tokenBase(stream, state) {
      // stream.eatWhile(/[^\{]/);
      if (comment = stream.match(/^\{!--/, false)) {
        state.tokenize = inComment();
        return 'tag';
      } else if (condition = stream.match(/^\{(if|if:elseif)\s/, false)) {
        state.tokenize = inCondition();
        return 'tag';
      } else if (condition = stream.match(/\{(if:else|\/if)\}/, false)) {
        state.tokenize = inConditionalKeyword();
        return 'punctuation';
      } else if (tag = stream.match(/\{\/?([\w:]+)/, false)) {
        state.tokenize = inEETag(tag[1]);
        return 'punctuation';
      }

      stream.next();
    }

    function inConditionalKeyword() {
      return function (stream, state) {
        if (stream.match(/(if:else|\/if)/)) {
          return 'keyword';
        }

        if (stream.match('{')) {
          return 'punctuation';
        }

        if (stream.match('}')) {
          state.tokenize = tokenBase;
          return 'punctuation';
        }

        stream.next();
        return 'punctuation';
      };
    }

    function inEETag(tagname) {
      return function (stream, state) {
        stream.eatWhile(/\s+/);

        if (stream.match(/^"(\\|\"|[^"])*?"/)) {
          return 'string';
        }

        if (stream.match(/^'(\\|\'|[^'])*?'/)) {
          return 'string';
        }

        var variable;

        if (variable = stream.match(/\w*([a-zA-Z]([\w:-]+\w)?|(\w[\w:-]+)?[a-zA-Z])\w*/)) {
          return variable[0] == tagname ? 'variable' : 'variable-2';
        }

        if (stream.match(/{|=/)) {
          return 'punctuation';
        }

        if (stream.match(/}/)) {
          state.tokenize = tokenBase;
          return 'punctuation';
        }

        stream.next();
        return 'punctuation';
      };
    }

    function inCondition() {
      return function (stream, state) {
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

        if (stream.match(/"(\\|\"|[^"])*?"/)) {
          return 'string';
        }

        if (stream.match(/'(\\|\'|[^'])*?'/)) {
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

        if (stream.match(/{|=/)) {
          return 'punctuation';
        }

        if (stream.match(/}/)) {
          state.tokenize = tokenBase;
          return 'punctuation';
        }

        stream.next();
        return 'punctuation';
      };
    }

    function inComment() {
      return function (stream, state) {
        stream.eat(/\{!--/);

        if (stream.match(/^--}/, true)) {
          state.tokenize = tokenBase;
          return 'comment';
        }

        stream.next();
        return 'comment';
      };
    }

    return {
      startState: function startState() {
        return {
          tokenize: tokenBase
        };
      },
      token: function token(stream, state) {
        return state.tokenize(stream, state);
      },
      blockCommentStart: "{!--",
      blockCommentEnd: "--}"
    };
  }); // lay ee on top of the html mode

  CodeMirror.defineMode("ee", function (config) {
    var htmlBase = CodeMirror.getMode(config, "text/html");
    var eeInner = CodeMirror.getMode(config, "ee:inner");
    return CodeMirror.overlayMode(htmlBase, eeInner);
  });
  CodeMirror.defineMIME("text/x-ee", "ee");
})(CodeMirror);