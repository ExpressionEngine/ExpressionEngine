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
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
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
            if (this.props.componentDidMount != null) {
                this.props.componentDidMount();
            }

            // Bind the EE form validation to the color picker
            EE.cp.formValidation.bindInputs(ReactDOM.findDOMNode(this).parentNode);
        }
    }, {
        key: 'componentDidUpdate',
        value: function componentDidUpdate(prevProps, prevState) {
            if (this.state.selectedColor !== prevState.selectedColor) {
                // Notify when the color has changed
                this.colorChanged();

                // TODO: Should live preview be here?
                $(document).trigger('entry:preview');
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
            if (this.props.allowedColors == 'swatches') {
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
            var allowedColors = this.props.allowedColors;

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
                { className: 'c-colorpicker' },
                React.createElement('input', { 'class': 'c-colorpicker-input', type: 'text', id: this.props.inputId, name: this.props.inputName, value: this.state.inputValue, onChange: this.onInputChange, onFocus: this.showColorPanel, onBlur: this.hideColorPanel, autoComplete: 'off' }),
                React.createElement(
                    'span',
                    { className: 'c-colorpicker-input-color' },
                    React.createElement('span', { style: { background: currentColor.rgbaStr } })
                ),
                React.createElement(
                    'div',
                    { className: 'c-colorpicker-panel', style: { display: this.state.showPanel ? 'block' : 'none' }, onMouseDown: function onMouseDown(e) {
                            e.stopPropagation();e.preventDefault();
                        } },
                    allowedColors == 'any' && React.createElement(
                        'div',
                        { className: 'c-colorpicker-controls' },
                        React.createElement(
                            'div',
                            { className: 'c-colorpicker-hue-box', style: { background: hueColor }, onMouseDown: function onMouseDown(e) {
                                    return _this3.handleDrag(e, 'mouse', _this3.onHueBoxMove);
                                }, onTouchStart: function onTouchStart(e) {
                                    return _this3.handleDrag(e, 'touch', _this3.onHueBoxMove);
                                }, ref: function ref(el) {
                                    return _this3.hueBoxRef = el;
                                } },
                            React.createElement('div', { className: 'c-colorpicker-hue-box-knob', style: { top: hueKnobPosY, left: hueKnobPosX, background: hexStr }, ref: function ref(el) {
                                    return _this3.hueBoxKnobRef = el;
                                } })
                        ),
                        React.createElement(
                            'div',
                            { className: 'c-colorpicker-slider c-colorpicker-hue-slider', onMouseDown: function onMouseDown(e) {
                                    return _this3.handleDrag(e, 'mouse', _this3.onHueSliderMove);
                                }, onTouchStart: function onTouchStart(e) {
                                    return _this3.handleDrag(e, 'touch', _this3.onHueSliderMove);
                                }, ref: function ref(el) {
                                    return _this3.hueSliderRef = el;
                                } },
                            React.createElement('div', { className: 'c-colorpicker-slider-knob', ref: function ref(el) {
                                    return _this3.hueSliderKnobRef = el;
                                }, style: { background: hueColor, top: hueSliderPos } })
                        ),
                        this.props.enableOpacity && React.createElement(
                            'div',
                            { className: 'c-colorpicker-slider c-colorpicker-opacity-slider', onMouseDown: function onMouseDown(e) {
                                    return _this3.handleDrag(e, 'mouse', _this3.onOpacitySliderMove);
                                }, onTouchStart: function onTouchStart(e) {
                                    return _this3.handleDrag(e, 'touch', _this3.onOpacitySliderMove);
                                }, ref: function ref(el) {
                                    return _this3.opacitySliderRef = el;
                                } },
                            React.createElement('div', { className: 'c-colorpicker-slider-knob', ref: function ref(el) {
                                    return _this3.opacitySliderKnobRef = el;
                                }, style: { background: hexStr, top: opacitySliderPos } }),
                            React.createElement('div', { className: 'c-colorpicker-slider-inner', style: { background: 'linear-gradient(to top, ' + currentColor.withAlpha(0).rgbaStr + ', ' + hexStr + ')' } })
                        )
                    ),
                    React.createElement(
                        'div',
                        { className: 'c-colorpicker-swatches' },
                        this.props.swatches.map(function (colorStr, index) {
                            var color = new SimpleColor(colorStr);

                            if (!color.isValid) return '';

                            return React.createElement('div', { key: index, className: 'c-colorpicker-swatch ' + (color.rgbaStr == currentColor.rgbaStr ? 'is-selected' : ''), 'data-color': colorStr, onClick: _this3.onSwatchClick, style: { backgroundColor: color.rgbaStr, borderColor: color.shade(-15).rgbaStr } });
                        })
                    )
                )
            );
        }

        // -------------------------------------------------------------------

    }], [{
        key: 'renderFields',
        value: function renderFields(context) {
            var colorPickers = (context || document).querySelectorAll('input[data-colorpicker-react]');

            for (var index = 0; index < colorPickers.length; index++) {
                var container = colorPickers[index];
                // console.log(container, container.disabled);
                // continue;
                if (container.disabled) continue;

                var props = JSON.parse(window.atob(container.dataset.colorpickerReact));
                props.inputName = container.name;

                var newContainer = document.createElement('div');
                container.parentNode.replaceChild(newContainer, container);

                ReactDOM.render(React.createElement(ColorPicker, props, null), newContainer);
            }
        }
    }]);

    return ColorPicker;
}(React.Component);

// TODO: The color picker overflows the grid field


ColorPicker.defaultProps = {
    // The input name
    inputName: '',
    // The input id
    inputId: '',
    // The input color
    initialColor: '',

    // Allowed Colors:
    //  - any: Allows choosing any color. Both the swatches and color controls will be shown
    //  - swatches: Does not allow any color to be picked that's not in the swatches or default color. Only the swatches will be shown.
    allowedColors: 'any',
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