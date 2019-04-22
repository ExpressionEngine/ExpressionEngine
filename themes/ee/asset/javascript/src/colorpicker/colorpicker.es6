/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */


class ColorPicker extends React.Component {

    static defaultProps = {
        // The input name
        inputName: '',
        // The input id
        inputId: '',
        // The input color
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
    }

    lastChangeColor = null

    // -------------------------------------------------------------------

    constructor(props) {
        super(props)

        if (typeof SimpleColor === 'undefined') {
            console.error('Error: ColorPicker requires the SimpleColor class!')
            return
        }

        if (props.onChangeDelay != null) {
            this.colorChanged = _.throttle(this.colorChanged, props.onChangeDelay)
        }

        var initialColor = new SimpleColor(this.props.initialColor)
        initialColor = initialColor.isValid ? initialColor : null

        this.state = { selectedColor: this.getReturnColor(initialColor), showPanel: false, inputValue: this.getReturnColorStr(initialColor)}
    }

    componentDidMount() {
        if (this.props.componentDidMount != null)
            this.props.componentDidMount()
    }

    componentDidUpdate(prevProps, prevState) {
        if (this.state.selectedColor !== prevState.selectedColor) {
            // Notify when the color has changed
            this.colorChanged()
        }
    }

    // -------------------------------------------------------------------

    showColorPanel = () => {
        this.setState({ showPanel: true }, () => {
            // Trigger a re-render so the slider knobs can be positioned properly
            this.selectColor(this.getReturnColor(this.state.selectedColor))
        })
    }

    hideColorPanel = () => {
        this.setState({ showPanel: false })
        this.selectColor(this.getReturnColor(this.state.selectedColor))
    }

    /** Selects a color optionally setting the input value to something other than the selected color return string */
    selectColor(newColor, inputValue = null) {
        var inputValue = inputValue == null ? this.getReturnColorStr(newColor) : inputValue
        this.setState({ selectedColor: newColor, inputValue: inputValue })
    }

    /** Notifies that the color has changed by calling the onChange callback  */
    colorChanged() {
        if (!this.props.onChange)
            return

        var color = this.getReturnColorStr(this.state.selectedColor)

        if (this.lastChangeColor != color) {
            this.props.onChange(color)
            this.lastChangeColor = color
        }
    }

    // ------------------------------------------------------------------

    onInputChange = (event) => {
        var inputColor = new SimpleColor(event.target.value)

        if (!inputColor.isValid)
            inputColor = null

        this.selectColor(inputColor, event.target.value)
    }

    onSwatchClick = (event) => {
        var clickedColor = new SimpleColor(event.currentTarget.dataset.color)

        if (clickedColor.isValid)
            this.selectColor(clickedColor)
    }

    onHueBoxMove = (pos) => {
        var rectOffset = this.hueBoxRef.getBoundingClientRect()

        var x = this.clamp(pos.x - rectOffset.left, 0, this.hueBoxRef.offsetWidth)
        var y = this.clamp(pos.y - rectOffset.top,  0, this.hueBoxRef.offsetHeight)

        var newColor = this.getSafeSelectedColor().withHsv({ s: x / this.hueBoxRef.offsetWidth, v: 1 - (y / this.hueBoxRef.offsetHeight) })
        this.selectColor(newColor)
    }

    onHueSliderMove = (pos) => {
        var hueOffset = this.hueSliderRef.getBoundingClientRect()
        var posY      = this.clamp(pos.y - hueOffset.top, 0, this.hueSliderRef.offsetHeight)

        var newHue = posY / this.hueSliderRef.offsetHeight
        // Don't allow the hue to reach 1. When it's converted to rgb, a hue of one will become zero causing the slider to snap back to the top.
        newHue = newHue >= 1 ? 0.99999999 : newHue

        this.selectColor(this.getSafeSelectedColor().withHsv({ h: newHue }))
    }

    onOpacitySliderMove = (pos) => {
        var opacityOffset = this.opacitySliderRef.getBoundingClientRect()
        var posY = this.clamp(pos.y - opacityOffset.top, 0, this.opacitySliderRef.offsetHeight)

        // Subtract by one to invert the opacity so the slider knob starts at the top
        this.selectColor(this.getSafeSelectedColor().withAlpha(1 - (posY / this.opacitySliderRef.offsetHeight)))
    }

    // -------------------------------------------------------------------

    /* Gets the color that will be returned */
    getReturnColor(color) {
        if (color == null)
            return this.getDefaultColor()

        // Enforce opacity
        if (!this.props.enableOpacity && color.rgb.a != 1)
            color = color.withAlpha(1)

        // Make sure the color is in the swatches
        if (this.props.mode == 'swatches') {
            for (let swatch of this.props.swatches) {
                if (new SimpleColor(swatch).equalTo(color))
                    return color
            }

            return this.getDefaultColor()
        }

        return color
    }

    /** Gets the color string that will returned by the color picker */
    getReturnColorStr(color) {
        var returnColor = this.getReturnColor(color)

        if (returnColor == null)
            return ''

        if (this.props.enableOpacity && returnColor.rgb.a != 1)
            return returnColor.rgbaStr

        return returnColor.hexStr
    }

    /** Returns the selected color making sure it's not null */
    getSafeSelectedColor() {
        return this.state.selectedColor != null ? this.state.selectedColor : new SimpleColor({r: 1, g: 1, b: 1, a: 1})
    }

    /** Returns the default color or null if it's not valid */
    getDefaultColor() {
        var defaultColor = new SimpleColor(this.props.defaultColor)
        return defaultColor.isValid ? defaultColor : null
    }

    /** Gets the x and y pos from the event using the touch or mouse position */
    getClientPosFromEvent(event) {
        // Try to get the mouse position
        if (event.clientX != null && event.clientY != null)
            return { x: event.clientX, y: event.clientY }
        // Try to get the touch position
        else if (event.changedTouches != null && event.changedTouches[0] != null)
            return { x: event.changedTouches[0].clientX, y: event.changedTouches[0].clientY }

        return { x: 0, y: 0 }
    }

    handleDrag(event, eventType, callback) {
        const doCallback = (e) => {
            callback(this.getClientPosFromEvent(e))
            e.preventDefault()
        }

        var moveEventName = eventType == 'mouse' ? 'mousemove' : 'touchmove'
        var stopEventName = eventType == 'mouse' ? 'mouseup'   : 'touchend'

        window.addEventListener(moveEventName, doCallback)
        window.addEventListener(stopEventName, function finish() {
            window.removeEventListener(moveEventName, doCallback)
            window.removeEventListener(stopEventName, finish)
        })

        doCallback(event)
    }

    clamp(value, min, max) {
        return Math.min(Math.max(value, min), max)
    }

    // -------------------------------------------------------------------

    render() {
        var currentColor = this.getReturnColor(this.state.selectedColor)

        if (currentColor == null)
            currentColor = new SimpleColor({r: 1, g: 1, b: 1, a: 0})

        var { hsv, hexStr } = currentColor
        var hueColor        = new SimpleColor({h: hsv.h, s: 1, v: 1, a: 1}).hexStr
        var { mode }        = this.props

        var [hueKnobPosX, hueKnobPosY, hueSliderPos, opacitySliderPos] = Array(4).fill('px')

        // Get the hue knob position
        if (this.hueBoxRef != null && this.hueBoxKnobRef != null) {
            var halfSize = this.hueBoxKnobRef.offsetWidth / 2
            hueKnobPosX = Math.round((this.hueBoxRef.offsetWidth * hsv.s) - halfSize) + 'px'
            hueKnobPosY = Math.round((this.hueBoxRef.offsetHeight * (1 - hsv.v)) - halfSize) + 'px'
        }

        // Get the hue slider knob position
        if (this.hueSliderRef != null && this.hueSliderKnobRef != null) {
            hueSliderPos = Math.round(hsv.h * (this.hueSliderRef.offsetHeight - this.hueSliderKnobRef.offsetHeight)) + 'px'
        }

        // Get the opacity slider knob position
        if (this.opacitySliderRef != null && this.opacitySliderKnobRef != null) {
            opacitySliderPos = Math.round((1 - currentColor.rgb.a) * (this.opacitySliderRef.offsetHeight - this.opacitySliderKnobRef.offsetHeight)) + 'px'
        }

        return (
            <div className="colorpicker">
                <input type="text" id={this.props.inputId} name={this.props.inputName} value={this.state.inputValue} onChange={this.onInputChange} onFocus={this.showColorPanel} onBlur={this.hideColorPanel} autoComplete="off"/>
                <span className="colorpicker-input-color"><span style={{background: currentColor.rgbaStr}}></span></span>

                <div className="colorpicker-panel" style={{display: this.state.showPanel ? 'block' : 'none'}} onMouseDown={e => { e.stopPropagation(); e.preventDefault() }}>
                    { (mode == 'custom' || mode == 'both') &&
                    <div className="colorpicker-controls">
                        <div className="colorpicker-hue-box" style={{background: hueColor}} onMouseDown={(e) => this.handleDrag(e, 'mouse', this.onHueBoxMove)} onTouchStart={(e) => this.handleDrag(e, 'touch', this.onHueBoxMove)} ref={el => this.hueBoxRef = el}>
                            <div className="colorpicker-hue-box-knob" style={{top: hueKnobPosY, left: hueKnobPosX, background: hexStr}}  ref={el => this.hueBoxKnobRef = el}></div>
                        </div>

                        <div className="colorpicker-slider colorpicker-hue-slider" onMouseDown={e => this.handleDrag(e, 'mouse', this.onHueSliderMove)} onTouchStart={(e) => this.handleDrag(e, 'touch', this.onHueSliderMove)} ref={el => this.hueSliderRef = el}><div className="colorpicker-slider-knob" ref={el => this.hueSliderKnobRef = el} style={{background: hueColor, top: hueSliderPos}}></div></div>
                        { this.props.enableOpacity &&
                            <div className="colorpicker-slider colorpicker-opacity-slider" onMouseDown={e => this.handleDrag(e, 'mouse', this.onOpacitySliderMove)} onTouchStart={(e) => this.handleDrag(e, 'touch', this.onOpacitySliderMove)} ref={el => this.opacitySliderRef = el}><div className="colorpicker-slider-knob" ref={el => this.opacitySliderKnobRef = el} style={{background: hexStr, top: opacitySliderPos}}></div><div className="colorpicker-slider-inner" style={{background: `linear-gradient(to top, ${currentColor.withAlpha(0).rgbaStr}, ${hexStr})`}}></div></div>
                        }
                    </div>
                    }

                    { (mode == 'swatches' || mode == 'both') &&
                    <div className="colorpicker-swatches">
                        {
                            this.props.swatches.map((colorStr, index) => {
                                const color = new SimpleColor(colorStr)
                                return (<div key={index} className={`swatch ${color.rgbaStr == currentColor.rgbaStr ? 'selected' : ''}`} data-color={colorStr} onClick={this.onSwatchClick} style={ {backgroundColor: color.rgbaStr, borderColor: color.shade(-15).rgbaStr} }></div>)
                            })
                        }
                    </div>
                    }
                </div>
            </div>
        )
    }

    // -------------------------------------------------------------------
}









// TODO: Add this to the cp css
function tmpCss() {
    return `
<style>
.colorpicker {
    position: relative;
}

.colorpicker input {
    padding-left: 28px;
}
.colorpicker-input-color {
    pointer-events: none;
    display: inline-block;
    position: absolute;
    left: 6px;
    top: 6px;
    width: 14px;
    height: 14px;
    padding: 0;
    margin: 0;
    vertical-align: middle;

    border: solid 1px #ccc;
    cursor: text;

    /* Opacity Checks */
    background-image: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+CjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+Cjxzdmcgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDggOCIgdmVyc2lvbj0iMS4xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB4bWw6c3BhY2U9InByZXNlcnZlIiB4bWxuczpzZXJpZj0iaHR0cDovL3d3dy5zZXJpZi5jb20vIiBzdHlsZT0iZmlsbC1ydWxlOmV2ZW5vZGQ7Y2xpcC1ydWxlOmV2ZW5vZGQ7c3Ryb2tlLWxpbmVqb2luOnJvdW5kO3N0cm9rZS1taXRlcmxpbWl0OjEuNDE0MjE7Ij4KICAgIDxnIHRyYW5zZm9ybT0ibWF0cml4KDEsMCwwLDEsLTQsLTQpIj4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSI2IiB5PSIwIiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSIwIiB5PSIyIiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSIyIiB5PSIwIiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSIwIiB5PSI2IiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSI0IiB5PSIyIiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSI0IiB5PSI2IiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSI2IiB5PSI0IiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSIyIiB5PSI0IiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgIDwvZz4KPC9zdmc+Cg==);
    background-size: 12px;
    background-repeat: repeat;
}
.colorpicker-input-color span {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
}

.colorpicker-panel {
    width: auto;
    position: absolute;
    left: 0;
    margin-top: 3px;
    z-index: 105;
    padding-bottom: 10px;

    background-color: #fff;
    border: 1px solid #ccc;
    border-radius: 5px;
    box-shadow: 0 2px 4px 0 rgba(0,0,0,.08);
}


.colorpicker-controls {
    box-sizing: border-box;
    padding: 10px 10px 0 10px;
    display: flex;
    flex-direction: row;
}
/* Clear fix */
.colorpicker-controls:after {
  content: "";
  display: table;
  clear: both;
}

.colorpicker-slider {
    position: relative;
    height: 120px;
    width: 14px;
    touch-action: pan-y;

    cursor: row-resize;
    border-radius: 4px;

    /* Opacity Checks */
    background-image: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+CjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+Cjxzdmcgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDggOCIgdmVyc2lvbj0iMS4xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB4bWw6c3BhY2U9InByZXNlcnZlIiB4bWxuczpzZXJpZj0iaHR0cDovL3d3dy5zZXJpZi5jb20vIiBzdHlsZT0iZmlsbC1ydWxlOmV2ZW5vZGQ7Y2xpcC1ydWxlOmV2ZW5vZGQ7c3Ryb2tlLWxpbmVqb2luOnJvdW5kO3N0cm9rZS1taXRlcmxpbWl0OjEuNDE0MjE7Ij4KICAgIDxnIHRyYW5zZm9ybT0ibWF0cml4KDEsMCwwLDEsLTQsLTQpIj4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSI2IiB5PSIwIiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSIwIiB5PSIyIiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSIyIiB5PSIwIiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSIwIiB5PSI2IiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSI0IiB5PSIyIiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSI0IiB5PSI2IiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSI2IiB5PSI0IiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSIyIiB5PSI0IiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgIDwvZz4KPC9zdmc+Cg==);
    background-size: 20px;
    background-repeat: repeat;
}
.colorpicker-slider-inner {
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    z-index: 5;
    border-radius: 4px;
}
.colorpicker-slider-knob {
    width: 100%;
    height: 5px;
    position: absolute;
    top: 0;
    left: 0;
    z-index: 10;
    box-sizing: border-box;
    border-radius: 2px;
    border: 1px solid #fff;
    box-shadow: 0 0 0 1px #000;
    background-color: #fff;
}

.colorpicker-hue-slider {
    margin-left: 10px;
    background: linear-gradient(#ff0000 0%, #ffff00 17%, #00ff00 33%, #00ffff 50%, #0000ff 67%, #ff00ff 83%, #ff0000 100%);
}

.colorpicker-opacity-slider {
    margin-left: 10px;
}

.colorpicker-hue-box {
    touch-action: pan-x pan-y;
    margin: 0;
    flex-grow: 1;
    min-width: 150px;
    height: 120px;
    position: relative;
    cursor: crosshair;
    border-radius: 4px;
}
.colorpicker-hue-box:before, .colorpicker-hue-box:after {
    content: '';
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 4px;
}
/* Saturation Gradient */
.colorpicker-hue-box:before {
    z-index: 5;
    background: linear-gradient(to right, rgba(255,255,255,1) 0%, rgba(255,255,255,0) 100%);
}
/* Brightness Gradient */
.colorpicker-hue-box:after {
    z-index: 6;
    background: linear-gradient(to bottom, rgba(0,0,0,0) 0%,rgba(0,0,0,1) 100%);
}
.colorpicker-hue-box-knob {
    width: 12px;
    height: 12px;
    border: 2px solid #fff;
    border-radius: 50%;
    position: absolute;
    z-index: 10;
    top: 40px;
    left: 20px;
    box-shadow: inset 0 0 0 1px #000;
}

.colorpicker-swatches {
    padding: 0 10px 0 10px;
}
.colorpicker-swatches .swatch {
    width: 20px;
    height: 20px;
    float: left;
    margin: 8px 0 0 8px;
    border: solid 1px #fff;
    border-radius: 50%;
    cursor: pointer;
    box-sizing: border-box;
}
/* Wrap at every 7th swatch */
.colorpicker-swatches .swatch:nth-child(7n+1) {
   margin-left: 0;
   display: block;
   clear: both;
}
.colorpicker-swatches .swatch.selected {
    box-shadow: 0 0 0 2px #74C0FC;
}
</style>
`

}
