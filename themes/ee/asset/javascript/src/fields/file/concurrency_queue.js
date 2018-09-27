"use strict";

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

var ConcurrencyQueue = function () {
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
        _this.queue.push({ item: item, factory: factory });
      });
      this.start();
    }
  }, {
    key: "start",
    value: function start() {
      var _this2 = this;

      while (this.currentlyRunning < this.concurrency && this.queue.length > 0) {
        var _queue$shift = this.queue.shift(),
            item = _queue$shift.item,
            factory = _queue$shift.factory;

        this.currentlyRunning++;

        factory(item).then(function () {
          _this2.currentlyRunning--;
          _this2.start();
        });
      }
    }
  }]);

  return ConcurrencyQueue;
}();