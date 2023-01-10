/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
var __extends = (this && this.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
var Toggle = /** @class */ (function (_super) {
    __extends(Toggle, _super);
    function Toggle(props) {
        var _this = _super.call(this, props) || this;
        _this.handleClick = function (event) {
            event.preventDefault();
            _this.setState(function (prevState, props) {
                if (props.handleToggle) {
                    props.handleToggle(!prevState.on);
                }
                return {
                    on: !prevState.on,
                    value: (!prevState.on) ? props.offValue : props.onValue,
                    onOff: !prevState.on ? 'on' : 'off',
                    trueFalse: !prevState.on ? 'true' : 'false',
                };
            });
        };
        _this.state = {
            on: props.on,
            value: props.value,
            onOff: props.on ? 'on' : 'off',
            trueFalse: props.on ? 'true' : 'false'
        };
        return _this;
    }
    Toggle.prototype.render = function () {
        return (React.createElement("button", { type: "button", className: "toggle-btn " + this.state.onOff, onClick: this.handleClick, title: this.state.onOff, "data-state": this.state.onOff, "aria-checked": this.state.trueFalse, role: "switch" },
            this.props.name &&
                React.createElement("input", { type: "hidden", name: this.props.name, value: this.state.value }),
            React.createElement("span", { className: "slider" })));
    };
    return Toggle;
}(React.Component));
function ToggleTools(props) {
    return (React.createElement("div", { className: "toggle-tools" },
        React.createElement("b", null, props.label),
        props.children));
}