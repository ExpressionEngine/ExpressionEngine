/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */


// TODO: Add this to the cp css
var tmpCss = `
<style>
.colorpicker {
    position: relative;
}

.colorpicker-input {
    padding-left: 28px;
}
.colorpicker-input-color {
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
    display: none;
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
    background: -webkit-linear-gradient(#ff0000 0%, #ffff00 17%, #00ff00 33%, #00ffff 50%, #0000ff 67%, #ff00ff 83%, #ff0000 100%);
    background: -o-linear-gradient(#ff0000 0%, #ffff00 17%, #00ff00 33%, #00ffff 50%, #0000ff 67%, #ff00ff 83%, #ff0000 100%);
    background: linear-gradient(#ff0000 0%, #ffff00 17%, #00ff00 33%, #00ffff 50%, #0000ff 67%, #ff00ff 83%, #ff0000 100%);
}

.colorpicker-opacity-slider {
    margin-left: 10px;

    /* Opacity Checks */
    background-image: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+CjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+Cjxzdmcgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDggOCIgdmVyc2lvbj0iMS4xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB4bWw6c3BhY2U9InByZXNlcnZlIiB4bWxuczpzZXJpZj0iaHR0cDovL3d3dy5zZXJpZi5jb20vIiBzdHlsZT0iZmlsbC1ydWxlOmV2ZW5vZGQ7Y2xpcC1ydWxlOmV2ZW5vZGQ7c3Ryb2tlLWxpbmVqb2luOnJvdW5kO3N0cm9rZS1taXRlcmxpbWl0OjEuNDE0MjE7Ij4KICAgIDxnIHRyYW5zZm9ybT0ibWF0cml4KDEsMCwwLDEsLTQsLTQpIj4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSI2IiB5PSIwIiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSIwIiB5PSIyIiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSIyIiB5PSIwIiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSIwIiB5PSI2IiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSI0IiB5PSIyIiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSI0IiB5PSI2IiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSI2IiB5PSI0IiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLDQsNCkiPgogICAgICAgICAgICA8cmVjdCB4PSIyIiB5PSI0IiB3aWR0aD0iMiIgaGVpZ2h0PSIyIiBzdHlsZT0iZmlsbDpyZ2IoMjA0LDIwNCwyMDQpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgIDwvZz4KICAgIDwvZz4KPC9zdmc+Cg==);
    background-size: 20px;
    background-repeat: repeat;
}
.colorpicker-opacity-slider-inner {
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    z-index: 5;
    border-radius: 4px;
}

.colorpicker-hue-box-container {
    touch-action: pan-x pan-y;
    margin: 0;
    flex-grow: 1;
    min-width: 150px;
    height: 120px;
    position: relative;
    cursor: crosshair;
    border-radius: 4px;
}
.colorpicker-hue-box-saturation, .colorpicker-hue-box-brightness, .colorpicker-hue-box-color {
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    border-radius: 4px;
}
.colorpicker-hue-box-color {
    z-index: 4;
    border-radius: 5px;
}
.colorpicker-hue-box-saturation {
    z-index: 5;
    background: -moz-linear-gradient(left, rgba(255,255,255,1) 0%, rgba(255,255,255,0.99) 1%, rgba(255,255,255,0) 100%);
    background: -webkit-linear-gradient(left, rgba(255,255,255,1) 0%,rgba(255,255,255,0.99) 1%,rgba(255,255,255,0) 100%);
    background: linear-gradient(to right, rgba(255,255,255,1) 0%,rgba(255,255,255,0.99) 1%,rgba(255,255,255,0) 100%);
    filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff', endColorstr='#00ffffff',GradientType=1 );
}
.colorpicker-hue-box-brightness {
    z-index: 6;
    background: -moz-linear-gradient(top, rgba(0,0,0,0) 0%, rgba(0,0,0,1) 100%);
    background: -webkit-linear-gradient(top, rgba(0,0,0,0) 0%,rgba(0,0,0,1) 100%);
    background: linear-gradient(to bottom, rgba(0,0,0,0) 0%,rgba(0,0,0,1) 100%);
    filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#00000000', endColorstr='#000000',GradientType=0 );
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

.colorpicker-no-opacity .colorpicker-opacity-slider {
    display: none;
}

</style>
`


/**
 * A color picker.
 * When the color picker is initialized, it will use the color already in the input or the default color.
 *
 * @example
 * var picker = new ColorPicker(input, {
 *     onChange: function(newColor) {
 *         console.log('Color Changed!', newColor)
 *     }
 * });
 * 
 * @param {Element} input  The input to attach the color picker to.
 * @param {Object} options The settings of the color picker
 */
function ColorPicker(input, options) {

    var onChangeTimer    = null
    var lastChangeColor  = null

    var selectedColor    = null

    const defaultOptions = {
        // Modes:
        //  - custom: Only shows the color controls and allows any color to be picked.
        //  - swatches: Only shows the swatches and does not allow any color to be picked that's not in the swatches.
        //  - both: Shows the swatches and controls. Allows any color to be picked.
        mode: 'both',
        // Limits the amount of times the onChange callback is called in milliseconds
        onChangeDelay: 40,
        // Called when the color changes
        onChange: null,
        // The color to use when the user inputs an invalid color
        // Valid options:
        //  - Color String: If a valid hex or rgb string is supplied, that color will be used on invalid input 
        //  - 'swatches': When in swatch mode, the first color in the swatches will be used on invalid input
        //  - false: On invalid input the return color and input will become empty
        defaultColor: false,
        // An array of colors for the swatches
        swatches: [],
        // If false, prevents the color from having transparency and hides the opacity slider
        enableOpacity: false
    }

    var pickerContainer, pickerPanel, inputColorSwatch, 
    hueBoxContainer, hueBoxColor, hueBoxKnob,
    opacitySlider, opacitySliderKnob, opacitySliderInner, 
    hueSlider, hueSliderKnob, 
    swatchesContainer, pickerControlsContainer;

    // -------------------------------------------------------------------
    // Init
    // -------------------------------------------------------------------

    init()

    function init() {
        if (!isElement(input)) {
            console.error('Error creating ColorPicker: first argument must be an html element!')
            return
        }

        if (typeof SimpleColor === 'undefined') {
            console.error('Error: ColorPicker requires the SimpleColor class!')
            return
        }


        if (isInitialized()) return


        // Wrap the input with a container
        pickerContainer = createElementFromHTML('<div class="colorpicker"></div>')
        wrap(input, pickerContainer)

        addClass(input, 'colorpicker-input')

        // TODO: Remove me!
        pickerContainer.parentNode.insertBefore(createElementFromHTML(tmpCss),pickerContainer)

        // Create the color swatch thats inside of the input
        var swatch = createElementFromHTML('<span class="colorpicker-input-color"><span></span></span>')
        addEvent(swatch, 'click', function() { input.focus() })
        insertAfter(input, swatch)

        // Create the color panel
        var colorPanelHtml = `
            <div class="colorpicker-panel">
                <div class="colorpicker-controls">
                    <div class="colorpicker-hue-box-container">
                        <div class="colorpicker-hue-box-color"></div>
                        <div class="colorpicker-hue-box-saturation"></div>
                        <div class="colorpicker-hue-box-brightness"></div>
                        <div class="colorpicker-hue-box-knob"></div>
                    </div>

                    <div class="colorpicker-slider colorpicker-hue-slider"><div class="colorpicker-slider-knob colorpicker-hue-slider-knob"></div></div>
                    <div class="colorpicker-slider colorpicker-opacity-slider"><div class="colorpicker-slider-knob colorpicker-opacity-slider-knob"></div><div class="colorpicker-opacity-slider-inner"></div></div>
                </div>

                <div class="colorpicker-swatches"></div>
            </div>
        ` 

        insertAfter(input, createElementFromHTML(colorPanelHtml))

        // Now that the elements have been created, get references to them
        updateReferences()

        // Prevent the input from losing focus when the user clicks within the color panel
        addEvent(pickerPanel, 'mousedown', function(e) { e.stopPropagation(); e.preventDefault() })
        
        addEvent(input, 'focus', function() { show() })
        addEvent(input, 'blur',  function() { hide() })

        addEvent(input, 'keyup', function() { selectColorFromInput(true) })

        addEvent(hueBoxContainer, 'mousedown',  onHueBoxMouseDown)
        addEvent(hueBoxContainer, 'touchstart', onHueBoxTouchStart)

        addEvent(hueSlider,       'mousedown',  onHueSliderMouseDown)
        addEvent(hueSlider,       'touchstart', onHueSliderTouchStart)

        addEvent(opacitySlider,   'mousedown',  onOpacitySliderMouseDown)
        addEvent(opacitySlider,   'touchstart', onOpacitySliderTouchStart)

        // Set the options. This also selects the color in the input
        setOptions(options)

        // Mark the input as initialized
        input.setAttribute('colorpicker-initialized', 'true')
    }

    // -------------------------------------------------------------------
    // Methods
    // -------------------------------------------------------------------

    /** Selects a SimpleColor, updates the ui, and notifies the color has changed */
    function selectColor(newColor, updateImmediately) {
        selectedColor = newColor

        validateSelectedColor()

        updateToSelectedColor()

        colorChanged(updateImmediately)
    }

    /** Selects the current color string in the input, updates the ui, and notifies the color has changed */
    function selectColorFromInput(keepInputValue) {
        var inputColor = new SimpleColor(input.value)

        if (inputColor.isValid) {
            selectedColor = inputColor
        }
        else if (options.defaultColor.isValid) {
            selectedColor = new SimpleColor(options.defaultColor.rgb)
        }
        else {
            selectedColor = null
        }

        validateSelectedColor()

        updateToSelectedColor(keepInputValue)

        colorChanged(true)
    }

    // -------------------------------------------------------------------

    /** Calls the options.onChange callback */
    function colorChanged(callImmediately) {
        if (!isInitialized()) return

        // Should the callback be delayed?
        if (options.onChangeDelay && !callImmediately) {
            // Is the timer already running?
            if (onChangeTimer == null) {
                onChangeTimer = setTimeout(function() {
                    onChangeTimer = null
                    colorChanged(true)
                }, options.onChangeDelay)
            }

            return
        }

        // Make sure there's not a timeout running 
        clearTimeout(onChangeTimer)

        if (!isFunction(options.onChange)) return

        var color = getReturnColorStr()
        // Call the callback if the color has changed
        if (lastChangeColor != color) {
            lastChangeColor = color
            options.onChange(color)
        }
    }

    // -------------------------------------------------------------------

    /** Updates the color picker UI to show the selected color */
    function updateToSelectedColor(keepInputValue) {
        var color = selectedColor 

        if (color == null) {
            if (options.defaultColor.isValid)
                color = options.defaultColor
            else 
                color = new SimpleColor({r: 1, g: 1, b: 1, a: 0})
        }

        var hsv      = color.hsv
        var hexStr   = color.hexStr
        var hueColor = new SimpleColor({h: hsv.h, s: 1, v: 1, a: 1}).rgbaStr

        if (!keepInputValue) {
            input.value = getReturnColorStr()
        }

        inputColorSwatch.style.background = color.rgbaStr

        // Update the controls
        if (options.mode == 'custom' || options.mode == 'both') {
            // Opacity Slider
            moveOpacitySlider((1 - color.rgb.a) * (opacitySlider.offsetHeight - opacitySliderKnob.offsetHeight))
            var noOpacColor = color.withAlpha(0)
            opacitySliderInner.style.background     = `linear-gradient(to top, ${noOpacColor.rgbaStr}, ${color.hexStr})`
            opacitySliderKnob.style.backgroundColor = hexStr
       
            // Hue Slider
            moveHueSlider(hsv.h * (hueSlider.offsetHeight - hueSliderKnob.offsetHeight))
            hueSliderKnob.style.backgroundColor = hueColor

            // Hue Box
            moveHueBoxKnob(hueBoxContainer.offsetWidth * hsv.s, hueBoxContainer.offsetHeight * (1 - hsv.v))
            hueBoxKnob.style.backgroundColor = hexStr
            hueBoxColor.style.background     = hueColor
        }

        // Update the swatch selection
        if (options.mode == 'swatches' || options.mode == 'both') {
            var swatches = swatchesContainer.getElementsByClassName('swatch')
            var rgbaStr  = color.rgbaStr

            for(var i = 0; i < swatches.length; i++){
                var swatch = swatches[i]
                if (swatch.getAttribute('colorpicker-swatch-color') == rgbaStr)
                    addClass(swatch, 'selected')
                else 
                    removeClass(swatch, 'selected')
            }
        }
    }

    // -------------------------------------------------------------------

    /** Shows the color panel */
    function show() {
        pickerPanel.style.display = 'block'

        selectColorFromInput(false)
    }

    /** Hides the color panel */
    function hide() {
        pickerPanel.style.display = 'none'

        input.value = getReturnColorStr()
        selectColorFromInput(true)
    }

    // -------------------------------------------------------------------

    /** Sets/updates the color picker options. */
    function setOptions(newOptions) {

        // Merge the new options with the defaults 
        options = isObject(options) ? options : {}

        if (options.mode != 'both' && options.mode != 'custom' && options.mode != 'swatches') {
            console.error('Invalid color picker mode "' + options.mode + '"!')
            options.mode = defaultOptions.mode
        }

        // Replace any missing options with the defaults
        for (var key in defaultOptions) {
            var hasKey   = options.hasOwnProperty(key)
            options[key] = hasKey ? options[key] : defaultOptions[key]
        }

        // Should the default color be the first swatch?
        if (options.mode == 'swatches' && options.defaultColor == 'swatches') {
            var firstSwatch      = new SimpleColor(options.swatches[0])
            options.defaultColor = firstSwatch.isValid ? firstSwatch : new SimpleColor({r: 1, g: 1, b: 1, a: 1})
        } 
        else {
            // Turn the default color into a SimpleColor
            options.defaultColor = new SimpleColor(options.defaultColor)
        }
 
        // Show/hide the opacity slider
        if (!options.enableOpacity)
            addClass(pickerContainer, 'colorpicker-no-opacity')
        else 
            removeClass(pickerContainer, 'colorpicker-no-opacity')

        // Show/hide the controls
        var enableCustomControls = (options.mode == 'custom' || options.mode == 'both')
        pickerControlsContainer.style.display = enableCustomControls ? 'flex' : 'none'

        // Show/hide the swatches
        var enableSwatches = (options.mode == 'swatches' || options.mode == 'both')
        swatchesContainer.style.display = enableSwatches ? 'block' : 'none'

        swatchesContainer.innerHTML = ''

        if (enableSwatches) {
            var newSwatches = []

            for(var i = 0; i < options.swatches.length; i++) {
                var color = new SimpleColor(options.swatches[i])
                newSwatches.push(color)

                var darkerColor = color.shade(-15)
                var swatch      = createElementFromHTML('<div class="swatch" colorpicker-swatch-color="' + color.rgbaStr + '" style="background-color: ' + color.rgbaStr + '; border-color: ' + darkerColor.rgbaStr + ';"></div>')
                
                addEvent(swatch, 'click', function(e) {
                    var clickedColor = new SimpleColor(e.target.getAttribute('colorpicker-swatch-color'))
                    if (clickedColor.isValid)
                        selectColor(clickedColor, true)
                }) 

                swatchesContainer.appendChild(swatch)
            }

            // Replace the swatches string array with an array of SimpleColors
            options.swatches = newSwatches
        }

        // Update to the input to show any other changes
        selectColorFromInput(false)
    }

    // -------------------------------------------------------------------

    /** Gets references to all the color picker components using the picker container */
    function updateReferences() {
        if (pickerContainer == null) return

        pickerPanel             = pickerContainer.getElementsByClassName('colorpicker-panel')[0]
        hueBoxContainer         = pickerContainer.getElementsByClassName('colorpicker-hue-box-container')[0]
        hueBoxColor             = pickerContainer.getElementsByClassName('colorpicker-hue-box-color')[0]
        hueBoxKnob              = pickerContainer.getElementsByClassName('colorpicker-hue-box-knob')[0]
        opacitySlider           = pickerContainer.getElementsByClassName('colorpicker-opacity-slider')[0]
        opacitySliderKnob       = pickerContainer.getElementsByClassName('colorpicker-opacity-slider-knob')[0]
        opacitySliderInner      = pickerContainer.getElementsByClassName('colorpicker-opacity-slider-inner')[0]
        hueSlider               = pickerContainer.getElementsByClassName('colorpicker-hue-slider')[0]
        hueSliderKnob           = pickerContainer.getElementsByClassName('colorpicker-hue-slider-knob')[0]
        inputColorSwatch        = pickerContainer.getElementsByClassName('colorpicker-input-color')[0].getElementsByTagName('span')[0]
        swatchesContainer       = pickerContainer.getElementsByClassName('colorpicker-swatches')[0]
        pickerControlsContainer = pickerContainer.getElementsByClassName('colorpicker-controls')[0]
    }

    // -------------------------------------------------------------------

    /**
     * Enforces opacity when disabled. 
     * When in swatch mode, makes sure the selected color is in the swatches array 
     */
    function validateSelectedColor() {
        if (selectedColor != null && !options.enableOpacity && selectedColor.rgb.a != 1)
            selectedColor = selectedColor.withAlpha(1)

        if (options.mode != 'swatches' || selectedColor == null) return

        for(var i = 0; i < options.swatches.length; i++) {        
            if (options.swatches[i].equalTo(selectedColor))
                return
        }

        selectedColor = options.defaultColor.isValid ? options.defaultColor : null
    }

    /** Returns the selected color making sure it's not null */
    function getSafeSelectedColor() {
        if (selectedColor != null)
            return selectedColor
        else
            return new SimpleColor({r: 1, g: 1, b: 1, a: 1})
    }

    function getColorStr(color) {
        if (options.enableOpacity && color.rgb.a != 1)
            return color.rgbaStr

        return color.hexStr
    }

    /** Gets the return value of the color picker */
    function getReturnColorStr() {
        if (selectedColor != null)
            return getColorStr(selectedColor)
        else if (options.defaultColor.isValid)
            return getColorStr(options.defaultColor)
        else
            return ''
    }

    // -------------------------------------------------------------------

    function moveOpacitySlider(posY) {
        opacitySliderKnob.style.top = Math.round(clamp(posY, 0, opacitySlider.offsetHeight)) + 'px'
    }

    function moveHueSlider(posY) {
        hueSliderKnob.style.top = Math.round(clamp(posY, 0, hueSlider.offsetHeight)) + 'px'
    }

    function moveHueBoxKnob(x, y) {
        x = clamp(x, 0, hueBoxContainer.offsetWidth)
        y = clamp(y, 0, hueBoxContainer.offsetHeight)

        var halfSize = hueBoxKnob.offsetWidth / 2

        // Subtract half the size of the button to center it at the position
        hueBoxKnob.style.left = Math.round(x - halfSize) + 'px'
        hueBoxKnob.style.top  = Math.round(y - halfSize) + 'px'
    }

    // -------------------------------------------------------------------
    // Events
    // -------------------------------------------------------------------

    function onOpacitySliderMouseDown(event) {
        addEvent(window, 'mousemove', onOpacitySliderMove)
        addEvent(window, 'mouseup',   onOpacitySliderMouseUp)

        onOpacitySliderMove(event)
    }

    function onOpacitySliderTouchStart() {
        addEvent(window, 'touchmove', onOpacitySliderTouchMove)
        addEvent(window, 'touchend',  onOpacitySliderTouchEnd)

        onOpacitySliderTouchMove(event)
    }

    function onOpacitySliderTouchMove(event) {
        onOpacitySliderMove(event)
        event.preventDefault()
    }

    function onOpacitySliderMove(event) {
        var opacityOffset = opacitySlider.getBoundingClientRect()
        var posY = clamp(getClientPosFromEvent(event).y - opacityOffset.top, 0, opacitySlider.offsetHeight)

        // Subtract by one to invert the opacity
        var opacity = 1 - ((posY / opacitySlider.offsetHeight))

        selectColor(getSafeSelectedColor().withAlpha(opacity))
    }

    function onOpacitySliderMouseUp() {
        removeEvent(window, 'mousemove', onOpacitySliderMove)
        removeEvent(window, 'mouseup', onOpacitySliderMouseUp)
    }

    function onOpacitySliderTouchEnd() {
        removeEvent(window, 'touchmove', onOpacitySliderTouchMove)
        removeEvent(window, 'touchend', onOpacitySliderTouchEnd)
    }

    // -------------------------------------------------------------------

    function onHueSliderMouseDown(event) {
        addEvent(window, 'mousemove', onHueSliderMove)
        addEvent(window, 'mouseup', onHueSliderMouseUp)
 
        onHueSliderMove(event)
    }

    function onHueSliderTouchStart() {
        addEvent(window, 'touchmove', onHueSliderTouchMove)
        addEvent(window, 'touchend',  onHueSliderTouchEnd)

        onHueSliderTouchMove(event)
    }

    function onHueSliderTouchMove(event) {
        onHueSliderMove(event)
        event.preventDefault()
    }

    function onHueSliderMove(event) {
        var hueOffset = hueSlider.getBoundingClientRect()
        var posY      = clamp(getClientPosFromEvent(event).y - hueOffset.top, 0, hueSlider.offsetHeight)

        var newHue = (posY / hueSlider.offsetHeight)
        // Don't allow the hue to reach 1. When it's converted to rgb, one will become zero causing the hue slider to snap back to the top
        newHue = newHue >= 1 ? 0.99999999 : newHue

        var hsv = getSafeSelectedColor().hsv
        var newColor = new SimpleColor({h: newHue, s: hsv.s, v: hsv.v, a: hsv.a})

        selectColor(newColor)
    }

    function onHueSliderMouseUp() {
        removeEvent(window, 'mousemove', onHueSliderMove)
        removeEvent(window, 'mouseup', onHueSliderMouseUp)
    }

    function onHueSliderTouchEnd() {
        removeEvent(window, 'touchmove', onHueSliderTouchMove)
        removeEvent(window, 'touchend', onHueSliderTouchEnd)
    }

    // -------------------------------------------------------------------

    function onHueBoxMouseDown(event) {
        addEvent(window, 'mousemove', onHueBoxMove)
        addEvent(window, 'mouseup', onHueBoxMouseUp)

        onHueBoxMove(event)
    }

    function onHueBoxTouchStart() {
        addEvent(window, 'touchmove', onHueBoxTouchMove)
        addEvent(window, 'touchend',  onHueBoxTouchEnd)

        onHueBoxTouchMove(event)
    }

    function onHueBoxTouchMove(event) {
        onHueBoxMove(event)
        event.preventDefault()
    }

    function onHueBoxMove(event) {
        var safeColor  = getSafeSelectedColor()
        var rectOffset = hueBoxContainer.getBoundingClientRect()
        var pos        = getClientPosFromEvent(event)

        var x = clamp(pos.x - rectOffset.left, 0, hueBoxContainer.offsetWidth)
        var y = clamp(pos.y - rectOffset.top, 0, hueBoxContainer.offsetHeight)

        var hsv = {h: safeColor.hsv.h, s: x / hueBoxContainer.offsetWidth, v: 1 - (y / hueBoxContainer.offsetHeight), a: safeColor.rgb.a}
        selectColor(new SimpleColor(hsv))
    }

    function onHueBoxMouseUp() {
        removeEvent(window, 'mousemove', onHueBoxMove)
        removeEvent(window, 'mouseup', onHueBoxMouseUp)
    }
    
    function onHueBoxTouchEnd() {
        removeEvent(window, 'touchmove', onHueBoxTouchMove)
        removeEvent(window, 'touchend', onHueBoxTouchEnd)
    }

    // -------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------

    function isInitialized() {
        return input.getAttribute('colorpicker-initialized') == 'true'
    }

    // Gets the x and y pos from the event using the touch or mouse position
    function getClientPosFromEvent(event) {
        // Try to get the mouse position
        if (event.clientX != null && event.clientY != null) {
            return { x: event.clientX, y: event.clientY }
        }
        // Try to get the touch position
        else if (event.changedTouches != null && event.changedTouches[0] != null) {
            return { x: event.changedTouches[0].clientX, y: event.changedTouches[0].clientY }
        }

        return { x: 0, y: 0 }
    }

    function clamp(value, min, max) { 
        return Math.min(Math.max(value, min), max) 
    }

    function hasClass(el, className) {
        return el.classList ? el.classList.contains(className) : new RegExp('\\b'+ className+'\\b').test(el.className);
    }

    function addClass(el, className) {
        if (el.classList) el.classList.add(className);
        else if (!hasClass(el, className)) el.className += ' ' + className;
    }

    function removeClass(el, className) {
        if (el.classList) el.classList.remove(className);
        else el.className = el.className.replace(new RegExp('\\b'+ className+'\\b', 'g'), '');
    }

    // Creates an html element from a string
    function createElementFromHTML(htmlString) {
        var div = document.createElement('div');
        div.innerHTML = htmlString.trim();

        return div.firstChild; 
    }

    function insertAfter(element, newEl) {
        element.parentNode.insertBefore(newEl, element.nextSibling);
    }

    function wrap(element, wrapper) {
        element.parentNode.insertBefore(wrapper, element);
        wrapper.appendChild(element);
    }

    function addEvent(element, type, handler) {
        if (element.attachEvent) element.attachEvent('on' + type, handler); 
        else element.addEventListener(type, handler);
    }

    function removeEvent(element, type, handler) {
        if (element.detachEvent) element.detachEvent('on' + type, handler); 
        else element.removeEventListener(type, handler);
    }

    function isFunction(obj) {
        return obj && typeof obj === "function"
    }

    function isElement(obj) {
        return !!(obj && obj.nodeType === 1)
    }

    function isObject(obj) {
        return obj != null && (typeof obj === 'function' || typeof obj === 'object')
    }

    // -------------------------------------------------------------------
    // Public
    // -------------------------------------------------------------------

    return {
        setOptions: setOptions,
        getSelectedColor: function() {
            return getReturnColorStr()
        },
        selectColor: function(newColor) {
            newColor = newColor == null ? null : new SimpleColor(newColor)
            selectColor(newColor, true)
        }
    }
}
