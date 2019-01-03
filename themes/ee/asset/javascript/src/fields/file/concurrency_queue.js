"use strict";

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */
var ConcurrencyQueue =
/*#__PURE__*/
function () {
  function ConcurrencyQueue(_ref) {
    var concurrency = _ref.concurrency;

    _classCallCheck(this, ConcurrencyQueue);

    this.concurrency = concurrency;
    this.queue = [];
    this.currentlyRunning = 0;
  }

  _createClass(ConcurrencyQueue, [{
    key: "enqueue",
    value: function enqueue(items, factory) {
      var _this = this;

      items.forEach(function (item) {
        _this.queue.push({
          item: item,
          factory: factory
        });
      });
      this.start();
    }
  }, {
    key: "start",
    value: function start() {
      var _this2 = this;

      while (this.currentlyRunning < this.concurrency && this.queue.length > 0) {
        var _this$queue$shift = this.queue.shift(),
            item = _this$queue$shift.item,
            factory = _this$queue$shift.factory;

        this.currentlyRunning++;
        factory(item).then(function () {
          _this2.currentlyRunning--;

          _this2.start();
        }).catch(function () {
          _this2.currentlyRunning--;

          _this2.start();
        });
      }
    }
  }]);

  return ConcurrencyQueue;
}();