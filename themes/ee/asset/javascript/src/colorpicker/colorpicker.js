'use strict';

var _slicedToArray = function () { function sliceIterator(arr, i) { var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"]) _i["return"](); } finally { if (_d) throw _e; } } return _arr; } return function (arr, i) { if (Array.isArray(arr)) { return arr; } else if (Symbol.iterator in Object(arr)) { return sliceIterator(arr, i); } else { throw new TypeError("Invalid attempt to destructure non-iterable instance"); } }; }();

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

var ColorPicker = function (_React$Component) {
    _inherits(ColorPicker, _React$Component);

    // -------------------------------------------------------------------

    function ColorPicker(props) {
        _classCallCheck(this, ColorPicker);

        var _this = _possibleConstructorReturn(this, (ColorPicker.__proto__ || Object.getPrototypeOf(ColorPicker)).call(this, props));

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

            if (!inputColor.isValid) inputColor = null;

            _this.selectColor(inputColor, event.target.value);
        };

        _this.onSwatchClick = function (event) {
            var clickedColor = new SimpleColor(event.currentTarget.dataset.color);

            if (clickedColor.isValid) _this.selectColor(clickedColor);
        };

        _this.onHueBoxMove = function (pos) {
            var rectOffset = _this.hueBoxRef.getBoundingClientRect();

            var x = _this.clamp(pos.x - rectOffset.left, 0, _this.hueBoxRef.offsetWidth);
            var y = _this.clamp(pos.y - rectOffset.top, 0, _this.hueBoxRef.offsetHeight);

            var newColor = _this.getSafeSelectedColor().withHsv({ s: x / _this.hueBoxRef.offsetWidth, v: 1 - y / _this.hueBoxRef.offsetHeight });
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
            _this.selectColor(_this.getSafeSelectedColor().withAlpha(1 - posY / _this.opacitySliderRef.offsetHeight));
        };

        if (typeof SimpleColor === 'undefined') {
            console.error('Error: ColorPicker requires the SimpleColor class!');
            return _possibleConstructorReturn(_this);
        }

        if (props.onChangeDelay != null) {
            _this.colorChanged = _.throttle(_this.colorChanged, props.onChangeDelay);
        }

        var initialColor = new SimpleColor(_this.props.initialColor);
        initialColor = initialColor.isValid ? initialColor : null;

        _this.state = { selectedColor: _this.getReturnColor(initialColor), showPanel: false, inputValue: _this.getReturnColorStr(initialColor) };
        return _this;
    }

    _createClass(ColorPicker, [{
        key: 'componentDidMount',
        value: function componentDidMount() {
            if (this.props.componentDidMount != null) this.props.componentDidMount();
        }
    }, {
        key: 'componentDidUpdate',
        value: function componentDidUpdate(prevProps, prevState) {
            if (this.state.selectedColor !== prevState.selectedColor) {
                // Notify when the color has changed
                this.colorChanged();
            }
        }

        // -------------------------------------------------------------------

    }, {
        key: 'selectColor',


        /** Selects a color optionally setting the input value to something other than the selected color return string */
        value: function selectColor(newColor) {
            var inputValue = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;

            var inputValue = inputValue == null ? this.getReturnColorStr(newColor) : inputValue;
            this.setState({ selectedColor: newColor, inputValue: inputValue });
        }

        /** Notifies that the color has changed by calling the onChange callback  */

    }, {
        key: 'colorChanged',
        value: function colorChanged() {
            if (!this.props.onChange) return;

            var color = this.getReturnColorStr(this.state.selectedColor);

            if (this.lastChangeColor != color) {
                this.props.onChange(color);
                this.lastChangeColor = color;
            }
        }

        // ------------------------------------------------------------------

    }, {
        key: 'getReturnColor',


        // -------------------------------------------------------------------

        /* Gets the color that will be returned */
        value: function getReturnColor(color) {
            if (color == null) return this.getDefaultColor();

            // Enforce opacity
            if (!this.props.enableOpacity && color.rgb.a != 1) color = color.withAlpha(1);

            // Make sure the color is in the swatches
            if (this.props.mode == 'swatches') {
                var _iteratorNormalCompletion = true;
                var _didIteratorError = false;
                var _iteratorError = undefined;

                try {
                    for (var _iterator = this.props.swatches[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
                        var swatch = _step.value;

                        if (new SimpleColor(swatch).equalTo(color)) return color;
                    }
                } catch (err) {
                    _didIteratorError = true;
                    _iteratorError = err;
                } finally {
                    try {
                        if (!_iteratorNormalCompletion && _iterator.return) {
                            _iterator.return();
                        }
                    } finally {
                        if (_didIteratorError) {
                            throw _iteratorError;
                        }
                    }
                }

                return this.getDefaultColor();
            }

            return color;
        }

        /** Gets the color string that will returned by the color picker */

    }, {
        key: 'getReturnColorStr',
        value: function getReturnColorStr(color) {
            var returnColor = this.getReturnColor(color);

            if (returnColor == null) return '';

            if (this.props.enableOpacity && returnColor.rgb.a != 1) return returnColor.rgbaStr;

            return returnColor.hexStr;
        }

        /** Returns the selected color making sure it's not null */

    }, {
        key: 'getSafeSelectedColor',
        value: function getSafeSelectedColor() {
            return this.state.selectedColor != null ? this.state.selectedColor : new SimpleColor({ r: 1, g: 1, b: 1, a: 1 });
        }

        /** Returns the default color or null if it's not valid */

    }, {
        key: 'getDefaultColor',
        value: function getDefaultColor() {
            var defaultColor = new SimpleColor(this.props.defaultColor);
            return defaultColor.isValid ? defaultColor : null;
        }

        /** Gets the x and y pos from the event using the touch or mouse position */

    }, {
        key: 'getClientPosFromEvent',
        value: function getClientPosFromEvent(event) {
            // Try to get the mouse position
            if (event.clientX != null && event.clientY != null) return { x: event.clientX, y: event.clientY
                // Try to get the touch position
            };else if (event.changedTouches != null && event.changedTouches[0] != null) return { x: event.changedTouches[0].clientX, y: event.changedTouches[0].clientY };

            return { x: 0, y: 0 };
        }
    }, {
        key: 'handleDrag',
        value: function handleDrag(event, eventType, callback) {
            var _this2 = this;

            var doCallback = function doCallback(e) {
                callback(_this2.getClientPosFromEvent(e));
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
        }
    }, {
        key: 'clamp',
        value: function clamp(value, min, max) {
            return Math.min(Math.max(value, min), max);
        }

        // -------------------------------------------------------------------

    }, {
        key: 'render',
        value: function render() {
            var _this3 = this;

            var currentColor = this.getReturnColor(this.state.selectedColor);

            if (currentColor == null) currentColor = new SimpleColor({ r: 1, g: 1, b: 1, a: 0 });

            var _currentColor = currentColor,
                hsv = _currentColor.hsv,
                hexStr = _currentColor.hexStr;

            var hueColor = new SimpleColor({ h: hsv.h, s: 1, v: 1, a: 1 }).hexStr;
            var mode = this.props.mode;

            var _Array$fill = Array(4).fill('px'),
                _Array$fill2 = _slicedToArray(_Array$fill, 4),
                hueKnobPosX = _Array$fill2[0],
                hueKnobPosY = _Array$fill2[1],
                hueSliderPos = _Array$fill2[2],
                opacitySliderPos = _Array$fill2[3];

            // Get the hue knob position


            if (this.hueBoxRef != null && this.hueBoxKnobRef != null) {
                var halfSize = this.hueBoxKnobRef.offsetWidth / 2;
                hueKnobPosX = Math.round(this.hueBoxRef.offsetWidth * hsv.s - halfSize) + 'px';
                hueKnobPosY = Math.round(this.hueBoxRef.offsetHeight * (1 - hsv.v) - halfSize) + 'px';
            }

            // Get the hue slider knob position
            if (this.hueSliderRef != null && this.hueSliderKnobRef != null) {
                hueSliderPos = Math.round(hsv.h * (this.hueSliderRef.offsetHeight - this.hueSliderKnobRef.offsetHeight)) + 'px';
            }

            // Get the opacity slider knob position
            if (this.opacitySliderRef != null && this.opacitySliderKnobRef != null) {
                opacitySliderPos = Math.round((1 - currentColor.rgb.a) * (this.opacitySliderRef.offsetHeight - this.opacitySliderKnobRef.offsetHeight)) + 'px';
            }

            return React.createElement(
                'div',
                { className: 'colorpicker' },
                React.createElement('input', { type: 'text', id: this.props.inputId, name: this.props.inputName, value: this.state.inputValue, onChange: this.onInputChange, onFocus: this.showColorPanel, onBlur: this.hideColorPanel, autoComplete: 'off' }),
                React.createElement(
                    'span',
                    { className: 'colorpicker-input-color' },
                    React.createElement('span', { style: { background: currentColor.rgbaStr } })
                ),
                React.createElement(
                    'div',
                    { className: 'colorpicker-panel', style: { display: this.state.showPanel ? 'block' : 'none' }, onMouseDown: function onMouseDown(e) {
                            e.stopPropagation();e.preventDefault();
                        } },
                    (mode == 'custom' || mode == 'both') && React.createElement(
                        'div',
                        { className: 'colorpicker-controls' },
                        React.createElement(
                            'div',
                            { className: 'colorpicker-hue-box', style: { background: hueColor }, onMouseDown: function onMouseDown(e) {
                                    return _this3.handleDrag(e, 'mouse', _this3.onHueBoxMove);
                                }, onTouchStart: function onTouchStart(e) {
                                    return _this3.handleDrag(e, 'touch', _this3.onHueBoxMove);
                                }, ref: function ref(el) {
                                    return _this3.hueBoxRef = el;
                                } },
                            React.createElement('div', { className: 'colorpicker-hue-box-knob', style: { top: hueKnobPosY, left: hueKnobPosX, background: hexStr }, ref: function ref(el) {
                                    return _this3.hueBoxKnobRef = el;
                                } })
                        ),
                        React.createElement(
                            'div',
                            { className: 'colorpicker-slider colorpicker-hue-slider', onMouseDown: function onMouseDown(e) {
                                    return _this3.handleDrag(e, 'mouse', _this3.onHueSliderMove);
                                }, onTouchStart: function onTouchStart(e) {
                                    return _this3.handleDrag(e, 'touch', _this3.onHueSliderMove);
                                }, ref: function ref(el) {
                                    return _this3.hueSliderRef = el;
                                } },
                            React.createElement('div', { className: 'colorpicker-slider-knob', ref: function ref(el) {
                                    return _this3.hueSliderKnobRef = el;
                                }, style: { background: hueColor, top: hueSliderPos } })
                        ),
                        this.props.enableOpacity && React.createElement(
                            'div',
                            { className: 'colorpicker-slider colorpicker-opacity-slider', onMouseDown: function onMouseDown(e) {
                                    return _this3.handleDrag(e, 'mouse', _this3.onOpacitySliderMove);
                                }, onTouchStart: function onTouchStart(e) {
                                    return _this3.handleDrag(e, 'touch', _this3.onOpacitySliderMove);
                                }, ref: function ref(el) {
                                    return _this3.opacitySliderRef = el;
                                } },
                            React.createElement('div', { className: 'colorpicker-slider-knob', ref: function ref(el) {
                                    return _this3.opacitySliderKnobRef = el;
                                }, style: { background: hexStr, top: opacitySliderPos } }),
                            React.createElement('div', { className: 'colorpicker-slider-inner', style: { background: 'linear-gradient(to top, ' + currentColor.withAlpha(0).rgbaStr + ', ' + hexStr + ')' } })
                        )
                    ),
                    (mode == 'swatches' || mode == 'both') && React.createElement(
                        'div',
                        { className: 'colorpicker-swatches' },
                        this.props.swatches.map(function (colorStr, index) {
                            var color = new SimpleColor(colorStr);
                            return React.createElement('div', { key: index, className: 'swatch ' + (color.rgbaStr == currentColor.rgbaStr ? 'selected' : ''), 'data-color': colorStr, onClick: _this3.onSwatchClick, style: { backgroundColor: color.rgbaStr, borderColor: color.shade(-15).rgbaStr } });
                        })
                    )
                )
            );
        }

        // -------------------------------------------------------------------

    }]);

    return ColorPicker;
}(React.Component);

// TODO: Add this to the cp css


ColorPicker.defaultProps = {
    // Input setup
    inputName: '',
    inputId: '',
    initialColor: '',

    // Modes:
    //  - custom: Only shows the color controls. Allows any color to be picked.
    //  - swatches: Only shows the swatches. Does not allow any color to be picked that's not in the swatches.
    //  - both: Shows the swatches and controls. Allows any color to be picked.
    mode: 'both',
    // Called when the color changes.
    onChange: null,
    // Prevents the onChange callback from being called more than once within the specified amount of time (milliseconds).
    onChangeDelay: 50,
    // The color to use when the user inputs an invalid color
    // Valid options:
    //  - color string: If a valid hex or rgb string is supplied, that color will be used on invalid input
    //  - null: On invalid input the return color and input will become empty
    defaultColor: null,
    // An array of colors.
    swatches: [],
    // If false, prevents the color from having transparency and hides the opacity slider
    enableOpacity: false
};
function tmpCss() {
    return '\n<style>\n.colorpicker {\n    position: relative;\n}\n\n.colorpicker input {\n    padding-left: 28px;\n}\n.colorpicker-input-color {\n    pointer-events: none;\n    display: inline-block;\n    position: absolute;\n    left: 6px;\n    top: 6px;\n    width: 14px;\n    height: 14px;\n    padding: 0;\n    margin: 0;\n    vertical-align: middle;\n\n    border: solid 1px #ccc;\n    cursor: text;\n\n    /* Opacity Checks */\n    background-image: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+CjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+Cjxzdmcgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDggOCIgdmVyc2lvbj0iMS4xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB4bWw6c3BhY2U9InByZXNlcnZlIiB4bWxuczpzZXJpZj0iaHR0cDovL3d3dy5zZXJpZi5jb20vIiBzdHlsZT0iZmlsbC1ydWxlOmV2ZW5vZGQ7Y2xpcC1ydWxlOmV2ZW5vZGQ7c3Ryb2tlLWxpbmVqb2luOnJvdW5kO3N0cm9rZS1taXRlcmxpbWl0OjEuNDE0MjE7Ij4KICAgIDxnIHRyYW5zZm9ybT0ibWF0cml4KDEsMCwwLDEsLTQsLTQpIj4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSI2IiB5PSIwIiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSIwIiB5PSIyIiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSIyIiB5PSIwIiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSIwIiB5PSI2IiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSI0IiB5PSIyIiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSI0IiB5PSI2IiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSI2IiB5PSI0IiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSIyIiB5PSI0IiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgIDwvZz4KPC9zdmc+Cg==);\n    background-size: 12px;\n    background-repeat: repeat;\n}\n.colorpicker-input-color span {\n    position: absolute;\n    top: 0;\n    left: 0;\n    right: 0;\n    bottom: 0;\n}\n\n.colorpicker-panel {\n    width: auto;\n    position: absolute;\n    left: 0;\n    margin-top: 3px;\n    z-index: 105;\n    padding-bottom: 10px;\n\n    background-color: #fff;\n    border: 1px solid #ccc;\n    border-radius: 5px;\n    box-shadow: 0 2px 4px 0 rgba(0,0,0,.08);\n}\n\n\n.colorpicker-controls {\n    box-sizing: border-box;\n    padding: 10px 10px 0 10px;\n    display: flex;\n    flex-direction: row;\n}\n/* Clear fix */\n.colorpicker-controls:after {\n  content: "";\n  display: table;\n  clear: both;\n}\n\n.colorpicker-slider {\n    position: relative;\n    height: 120px;\n    width: 14px;\n    touch-action: pan-y;\n\n    cursor: row-resize;\n    border-radius: 4px;\n\n    /* Opacity Checks */\n    background-image: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+CjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+Cjxzdmcgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDggOCIgdmVyc2lvbj0iMS4xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB4bWw6c3BhY2U9InByZXNlcnZlIiB4bWxuczpzZXJpZj0iaHR0cDovL3d3dy5zZXJpZi5jb20vIiBzdHlsZT0iZmlsbC1ydWxlOmV2ZW5vZGQ7Y2xpcC1ydWxlOmV2ZW5vZGQ7c3Ryb2tlLWxpbmVqb2luOnJvdW5kO3N0cm9rZS1taXRlcmxpbWl0OjEuNDE0MjE7Ij4KICAgIDxnIHRyYW5zZm9ybT0ibWF0cml4KDEsMCwwLDEsLTQsLTQpIj4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSI2IiB5PSIwIiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSIwIiB5PSIyIiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSIyIiB5PSIwIiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSIwIiB5PSI2IiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSI0IiB5PSIyIiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSI0IiB5PSI2IiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSI2IiB5PSI0IiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSIyIiB5PSI0IiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgIDwvZz4KPC9zdmc+Cg==);\n    background-size: 20px;\n    background-repeat: repeat;\n}\n.colorpicker-slider-inner {\n    position: absolute;\n    top: 0;\n    right: 0;\n    bottom: 0;\n    left: 0;\n    z-index: 5;\n    border-radius: 4px;\n}\n.colorpicker-slider-knob {\n    width: 100%;\n    height: 5px;\n    position: absolute;\n    top: 0;\n    left: 0;\n    z-index: 10;\n    box-sizing: border-box;\n    border-radius: 2px;\n    border: 1px solid #fff;\n    box-shadow: 0 0 0 1px #000;\n    background-color: #fff;\n}\n\n.colorpicker-hue-slider {\n    margin-left: 10px;\n    background: linear-gradient(#ff0000 0%, #ffff00 17%, #00ff00 33%, #00ffff 50%, #0000ff 67%, #ff00ff 83%, #ff0000 100%);\n}\n\n.colorpicker-opacity-slider {\n    margin-left: 10px;\n}\n\n.colorpicker-hue-box {\n    touch-action: pan-x pan-y;\n    margin: 0;\n    flex-grow: 1;\n    min-width: 150px;\n    height: 120px;\n    position: relative;\n    cursor: crosshair;\n    border-radius: 4px;\n}\n.colorpicker-hue-box:before, .colorpicker-hue-box:after {\n    content: \'\';\n    position: absolute;\n    width: 100%;\n    height: 100%;\n    border-radius: 4px;\n}\n/* Saturation Gradient */\n.colorpicker-hue-box:before {\n    z-index: 5;\n    background: linear-gradient(to right, rgba(255,255,255,1) 0%, rgba(255,255,255,0) 100%);\n}\n/* Brightness Gradient */\n.colorpicker-hue-box:after {\n    z-index: 6;\n    background: linear-gradient(to bottom, rgba(0,0,0,0) 0%,rgba(0,0,0,1) 100%);\n}\n.colorpicker-hue-box-knob {\n    width: 12px;\n    height: 12px;\n    border: 2px solid #fff;\n    border-radius: 50%;\n    position: absolute;\n    z-index: 10;\n    top: 40px;\n    left: 20px;\n    box-shadow: inset 0 0 0 1px #000;\n}\n\n.colorpicker-swatches {\n    padding: 0 10px 0 10px;\n}\n.colorpicker-swatches .swatch {\n    width: 20px;\n    height: 20px;\n    float: left;\n    margin: 8px 0 0 8px;\n    border: solid 1px #fff;\n    border-radius: 50%;\n    cursor: pointer;\n    box-sizing: border-box;\n}\n/* Wrap at every 7th swatch */\n.colorpicker-swatches .swatch:nth-child(7n+1) {\n   margin-left: 0;\n   display: block;\n   clear: both;\n}\n.colorpicker-swatches .swatch.selected {\n    box-shadow: 0 0 0 2px #74C0FC;\n}\n</style>\n';
}