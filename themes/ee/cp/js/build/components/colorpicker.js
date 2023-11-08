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
var ColorPicker = /** @class */ (function (_super) {
    __extends(ColorPicker, _super);
    function ColorPicker(props) {
        var _this = _super.call(this, props) || this;
        _this.lastChangeColor = null;
        _this.showColorPanel = function () {
            _this.setState({ showPanel: true }, function () {
                // Trigger a re-render so the slider knobs can be positioned properly
                _this.selectColor(_this.getReturnColor(_this.state.selectedColor));
            });
        };
        _this.hideColorPanel = function () {
            _this.setState({ showPanel: false });
            _this.selectColor(_this.getReturnColor(_this.state.selectedColor));
        };
        _this.onInputChange = function (event) {
            var inputColor = new SimpleColor(event.target.value);
            if (!inputColor.isValid)
                inputColor = null;
            _this.selectColor(inputColor, event.target.value);
        };
        _this.onSwatchClick = function (event) {
            var clickedColor = new SimpleColor(event.currentTarget.dataset.color);
            if (clickedColor.isValid)
                _this.selectColor(clickedColor);
        };
        _this.onHueBoxMove = function (pos) {
            var rectOffset = _this.hueBoxRef.getBoundingClientRect();
            var x = _this.clamp(pos.x - rectOffset.left, 0, _this.hueBoxRef.offsetWidth);
            var y = _this.clamp(pos.y - rectOffset.top, 0, _this.hueBoxRef.offsetHeight);
            var newColor = _this.getSafeSelectedColor().withHsv({ s: x / _this.hueBoxRef.offsetWidth, v: 1 - (y / _this.hueBoxRef.offsetHeight) });
            _this.selectColor(newColor);
        };
        _this.onHueSliderMove = function (pos) {
            var hueOffset = _this.hueSliderRef.getBoundingClientRect();
            var posY = _this.clamp(pos.y - hueOffset.top, 0, _this.hueSliderRef.offsetHeight);
            var newHue = posY / _this.hueSliderRef.offsetHeight;
            // Don't allow the hue to reach 1. When it's converted to rgb, a hue of one will become zero causing the slider to snap back to the top.
            newHue = newHue >= 1 ? 0.99999999 : newHue;
            _this.selectColor(_this.getSafeSelectedColor().withHsv({ h: newHue }));
        };
        _this.onOpacitySliderMove = function (pos) {
            var opacityOffset = _this.opacitySliderRef.getBoundingClientRect();
            var posY = _this.clamp(pos.y - opacityOffset.top, 0, _this.opacitySliderRef.offsetHeight);
            // Subtract by one to invert the opacity so the slider knob starts at the top
            _this.selectColor(_this.getSafeSelectedColor().withAlpha(1 - (posY / _this.opacitySliderRef.offsetHeight)));
        };
        if (typeof SimpleColor === 'undefined') {
            console.error('Error: ColorPicker requires the SimpleColor class!');
            return _this;
        }
        if (props.onChangeDelay != null) {
            _this.colorChanged = _.throttle(_this.colorChanged, props.onChangeDelay);
        }
        var initialColor = new SimpleColor(_this.props.initialColor);
        initialColor = initialColor.isValid ? initialColor : null;
        _this.state = { selectedColor: _this.getReturnColor(initialColor), showPanel: false, inputValue: _this.getReturnColorStr(initialColor) };
        return _this;
    }
    ColorPicker.prototype.componentDidMount = function () {
        // Bind the EE form validation to the color picker
        EE.cp.formValidation.bindInputs(ReactDOM.findDOMNode(this).parentNode);
    };
    ColorPicker.prototype.componentDidUpdate = function (prevProps, prevState) {
        if (this.state.selectedColor !== prevState.selectedColor) {
            // Notify when the color has changed
            this.colorChanged();
        }
    };
    ColorPicker.renderFields = function (context) {
        var colorPickers = (context || document).querySelectorAll('input[data-colorpicker-react]');
        for (var index = 0; index < colorPickers.length; index++) {
            var container = colorPickers[index];
            if (container.disabled)
                continue;
            var props = JSON.parse(window.atob(container.dataset.colorpickerReact));
            props.inputName = container.name;
            var newContainer = document.createElement('div');
            container.parentNode.replaceChild(newContainer, container);
            ReactDOM.render(React.createElement(ColorPicker, props, null), newContainer);
        }
    };
    /** Selects a color optionally setting the input value to something other than the selected color return string */
    ColorPicker.prototype.selectColor = function (newColor, inputValue) {
        if (inputValue === void 0) { inputValue = null; }
        inputValue = inputValue == null ? this.getReturnColorStr(newColor) : inputValue;
        this.setState({ selectedColor: newColor, inputValue: inputValue });
    };
    /** Notifies that the color has changed by calling the onChange callback  */
    ColorPicker.prototype.colorChanged = function () {
        // Refresh live preview
        $(document).trigger('entry:preview', 225);
        if (!this.props.onChange)
            return;
        var color = this.getReturnColorStr(this.state.selectedColor);
        if (this.lastChangeColor != color) {
            this.props.onChange(color);
            this.lastChangeColor = color;
        }
    };
    /* Gets the color that will be returned */
    ColorPicker.prototype.getReturnColor = function (color) {
        if (color == null)
            return this.getDefaultColor();
        // Enforce opacity
        if (!this.props.enableOpacity && color.rgb.a != 1)
            color = color.withAlpha(1);
        // Make sure the color is in the swatches
        if (this.props.allowedColors == 'swatches') {
            for (var _i = 0, _a = this.props.swatches; _i < _a.length; _i++) {
                var swatch = _a[_i];
                if (new SimpleColor(swatch).equalTo(color))
                    return color;
            }
            return this.getDefaultColor();
        }
        return color;
    };
    /** Gets the color string that will returned by the color picker */
    ColorPicker.prototype.getReturnColorStr = function (color) {
        var returnColor = this.getReturnColor(color);
        if (returnColor == null)
            return '';
        if (this.props.enableOpacity && returnColor.rgb.a != 1)
            return returnColor.rgbaStr;
        return returnColor.hexStr.toUpperCase();
    };
    /** Returns the selected color making sure it's not null */
    ColorPicker.prototype.getSafeSelectedColor = function () {
        return this.state.selectedColor != null ? this.state.selectedColor : new SimpleColor({ r: 1, g: 1, b: 1, a: 1 });
    };
    /** Returns the default color or null if it's not valid */
    ColorPicker.prototype.getDefaultColor = function () {
        var defaultColor = new SimpleColor(this.props.defaultColor);
        return defaultColor.isValid ? defaultColor : null;
    };
    /** Gets the x and y pos from the event using the touch or mouse position */
    ColorPicker.prototype.getClientPosFromEvent = function (event) {
        // Try to get the mouse position
        if (event.clientX != null && event.clientY != null)
            return { x: event.clientX, y: event.clientY };
        // Try to get the touch position
        else if (event.changedTouches != null && event.changedTouches[0] != null)
            return { x: event.changedTouches[0].clientX, y: event.changedTouches[0].clientY };
        return { x: 0, y: 0 };
    };
    ColorPicker.prototype.handleDrag = function (event, eventType, callback) {
        var _this = this;
        var doCallback = function (e) {
            callback(_this.getClientPosFromEvent(e));
            e.preventDefault();
        };
        var moveEventName = eventType == 'mouse' ? 'mousemove' : 'touchmove';
        var stopEventName = eventType == 'mouse' ? 'mouseup' : 'touchend';
        window.addEventListener(moveEventName, doCallback);
        window.addEventListener(stopEventName, function finish() {
            window.removeEventListener(moveEventName, doCallback);
            window.removeEventListener(stopEventName, finish);
        });
        doCallback(event);
    };
    ColorPicker.prototype.clamp = function (value, min, max) {
        return Math.min(Math.max(value, min), max);
    };
    ColorPicker.prototype.render = function () {
        var _this = this;
        var currentColor = this.getReturnColor(this.state.selectedColor);
        if (currentColor == null)
            currentColor = new SimpleColor({ r: 1, g: 1, b: 1, a: 0 });
        var hsv = currentColor.hsv, hexStr = currentColor.hexStr;
        var hueColor = new SimpleColor({ h: hsv.h, s: 1, v: 1, a: 1 }).hexStr;
        var allowedColors = this.props.allowedColors;
        var _a = Array(4).fill('px'), hueKnobPosX = _a[0], hueKnobPosY = _a[1], hueSliderPos = _a[2], opacitySliderPos = _a[3];
        // Get the hue knob position
        if (this.hueBoxRef != null && this.hueBoxKnobRef != null) {
            var halfSize = this.hueBoxKnobRef.offsetWidth / 2;
            hueKnobPosX = Math.round((this.hueBoxRef.offsetWidth * hsv.s) - halfSize) + 'px';
            hueKnobPosY = Math.round((this.hueBoxRef.offsetHeight * (1 - hsv.v)) - halfSize) + 'px';
        }
        // Get the hue slider knob position
        if (this.hueSliderRef != null && this.hueSliderKnobRef != null) {
            hueSliderPos = Math.round(hsv.h * (this.hueSliderRef.offsetHeight - this.hueSliderKnobRef.offsetHeight)) + 'px';
        }
        // Get the opacity slider knob position
        if (this.opacitySliderRef != null && this.opacitySliderKnobRef != null) {
            opacitySliderPos = Math.round((1 - currentColor.rgb.a) * (this.opacitySliderRef.offsetHeight - this.opacitySliderKnobRef.offsetHeight)) + 'px';
        }
        return (React.createElement("div", { className: "colorpicker" },
            React.createElement("div", { className: "colorpicker__inner_wrapper" },
                React.createElement("input", { className: "colorpicker__input js-dropdown-toggle", type: "text", id: this.props.inputId, name: this.props.inputName, value: this.state.inputValue, onChange: this.onInputChange, onFocus: this.showColorPanel, onBlur: this.hideColorPanel, autoComplete: "off", "aria-label": EE.lang.colorpicker_input }),
                React.createElement("span", { className: "colorpicker__input-color", style: { borderColor: currentColor.shade(-15).rgbaStr } },
                    React.createElement("span", { style: { background: currentColor.rgbaStr } }))),
            React.createElement("div", { className: "colorpicker__panel", style: { display: this.state.showPanel ? 'block' : 'none' }, onMouseDown: function (e) { e.stopPropagation(); e.preventDefault(); } },
                (allowedColors == 'any') &&
                    React.createElement("div", { className: "colorpicker__controls" },
                        React.createElement("div", { className: "colorpicker__hue-box", style: { background: hueColor }, onMouseDown: function (e) { return _this.handleDrag(e, 'mouse', _this.onHueBoxMove); }, onTouchStart: function (e) { return _this.handleDrag(e, 'touch', _this.onHueBoxMove); }, ref: function (el) { return _this.hueBoxRef = el; } },
                            React.createElement("div", { className: "colorpicker__hue-box-knob", style: { top: hueKnobPosY, left: hueKnobPosX, background: hexStr }, ref: function (el) { return _this.hueBoxKnobRef = el; } })),
                        React.createElement("div", { className: "colorpicker__slider colorpicker__hue-slider", onMouseDown: function (e) { return _this.handleDrag(e, 'mouse', _this.onHueSliderMove); }, onTouchStart: function (e) { return _this.handleDrag(e, 'touch', _this.onHueSliderMove); }, ref: function (el) { return _this.hueSliderRef = el; } },
                            React.createElement("div", { className: "colorpicker__slider-knob", ref: function (el) { return _this.hueSliderKnobRef = el; }, style: { background: hueColor, top: hueSliderPos } })),
                        this.props.enableOpacity &&
                            React.createElement("div", { className: "colorpicker__slider colorpicker__opacity-slider", onMouseDown: function (e) { return _this.handleDrag(e, 'mouse', _this.onOpacitySliderMove); }, onTouchStart: function (e) { return _this.handleDrag(e, 'touch', _this.onOpacitySliderMove); }, ref: function (el) { return _this.opacitySliderRef = el; } },
                                React.createElement("div", { className: "colorpicker__slider-knob", ref: function (el) { return _this.opacitySliderKnobRef = el; }, style: { background: hexStr, top: opacitySliderPos } }),
                                React.createElement("div", { className: "colorpicker__slider-inner", style: { background: "linear-gradient(to top, " + currentColor.withAlpha(0).rgbaStr + ", " + hexStr + ")" } }))),
                React.createElement("div", { className: "colorpicker__swatches" }, this.props.swatches.map(function (colorStr, index) {
                    var color = new SimpleColor(colorStr);
                    if (!color.isValid)
                        return '';
                    return (React.createElement("div", { key: index, className: "colorpicker__swatch " + (color.rgbaStr == currentColor.rgbaStr ? 'is-selected' : ''), "data-color": colorStr, onClick: _this.onSwatchClick, style: { backgroundColor: color.rgbaStr, borderColor: color.shade(-15).rgbaStr } }));
                })))));
    };
    ColorPicker.defaultProps = {
        inputName: '',
        inputId: '',
        initialColor: '',
        allowedColors: 'any',
        onChange: null,
        onChangeDelay: 50,
        defaultColor: null,
        swatches: [],
        enableOpacity: false
    };
    return ColorPicker;
}(React.Component));
// TODO: The color picker overflows the grid field
// Render color picker inputs when created:
$(window).on('load', function () {
    $(document).ready(function () {
        ColorPicker.renderFields();
    });
});
var miniGridInit = function (context) {
    $('.fields-keyvalue', context).miniGrid({ grid_min_rows: 0, grid_max_rows: '' });
};
Grid.bind('colorpicker', 'displaySettings', function (el) {
    miniGridInit(el[0]);
    ColorPicker.renderFields(el[0]);
});
Grid.bind('colorpicker', 'display', function (cell) {
    ColorPicker.renderFields(cell[0]);
});
$(document).on('grid:addRow', function (cell) {
    ColorPicker.renderFields(cell[0]);
});
FluidField.on('colorpicker', 'add', function (field) {
    ColorPicker.renderFields(field[0]);
});
// Load any color pickers when the field manager selects a fieldtype
FieldManager.on('fieldModalDisplay', function (modal) {
    ColorPicker.renderFields(modal[0]);
});
$('input.color-picker').each(function () {
    var input = this;
    var inputName = input.name;
    var inputValue = input.value;
    $(input).wrap('<div>');
    var newContainer = $(input).parent();
    ReactDOM.render(React.createElement(ColorPicker, {
        inputName: inputName,
        initialColor: inputValue,
        allowedColors: 'any',
        swatches: ['FA5252', 'FD7E14', 'FCC419', '40C057', '228BE6', 'BE4BDB', 'F783AC'],
        onChange: function (newColor) {
            // Change colors
            input.value = newColor;
        }
    }, null), newContainer[0]);
});