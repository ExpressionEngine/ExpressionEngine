/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */


/**
 *  A class for basic color conversion, manipulation, and formatting.
 */
class SimpleColor {

    // -------------------------------------------------------------------

    /**
    * Create a new SimpleColor
    *
    * Valid init types:
    *    {r: 1, g: 1, b: 1}
    *    {r: 1, g: 1, b: 1, a: 1}
    *    {h: 1, s: 1, v: 1}
    *    {h: 1, s: 1, v: 1, a}
    *    #fff
    *    #ffffff
    *    #ffffff99
    *    rgb(255, 255, 255)
    *    rgba(255, 255, 255, 0.6)
    */
    constructor(color) {
        this.isValid   = false
        this.initValue = color

        this._rgba     = { r: 1, g: 1, b: 1, a: 1 }

        if(typeof color === 'string') {
            var fromHex = SimpleColor.hexToRgb(color)
            if (fromHex !== null) {
                this._rgba   = fromHex
                this.isValid = true
            }
            else {
                var rgb = SimpleColor.getRgbFromString(color)
                if (rgb !== null) {
                    this._rgba   = rgb
                    this.isValid = true
                }
            }
        }
        else if (this._isObject(color)) {
            if (this._objectHasKeys(color, ['r', 'g', 'b'])) {
                this._rgba   = color
                this.isValid = true
            }

            else if (this._objectHasKeys(color, ['h', 's', 'v'])) {
                this._rgba   = SimpleColor.hsvToRgb(this._safeHSVColor(color))
                this.isValid = true
            }
        }

        this._rgba = this._safeRGB(this._rgba)
    }

    // -------------------------------------------------------------------
    // Public
    // -------------------------------------------------------------------

    /** Returns the RGB values of this color */
    get rgb() {
        return this._rgba
    }

    /** Returns the RGB 255 values of this color */
    get rgb255() {
        return { r: Math.round(this._rgba.r * 255), g: Math.round(this._rgba.g * 255), b: Math.round(this._rgba.b * 255), a: this._rgba.a }
    }

    /** Returns the HSV values of this color */
    get hsv() {
        return SimpleColor.rgbToHsv(this._rgba)
    }

    /** The perceived brightness of this color represented in a 0 to 1 value */
    get brightness() {
        var rgb255 = this.rgb255
        return (rgb255.r * 299 + rgb255.g * 587 + rgb255.b * 114) / 1000
    }

    /** Returns a hex string representing this color */
    get hexStr() {
        var rgb255 = this.rgb255

        function componentToHex(c) {
            var hex = c.toString(16)
            return hex.length == 1 ? '0' + hex : hex
        }

        return '#' + componentToHex(rgb255.r) + componentToHex(rgb255.g) + componentToHex(rgb255.b)
    }

    /** Returns a css rgba() string representing this color */
    get rgbaStr() {
        var rgb255 = this.rgb255
        return 'rgba(' + rgb255.r + ', ' + rgb255.g + ', ' + rgb255.b + ', ' + this._roundToPlaces(this._rgba.a, 2) + ')'
    }

    // -------------------------------------------------------------------

    /** Returns true or false if this color is considered dark */
    isDark() {
        return this.brightness < 130
    }

    /** Returns a black or white color in contrast to this color */
    fullContrastColor() {
        return this.isDark() ? SimpleColor.white : SimpleColor.black
    }

    /** Darkens or lightens the color with a -100 to 100 percentage */
    shade(percent) {
        return new SimpleColor(
            {r: this._rgba.r + (percent / 100), g: this._rgba.g + (percent / 100), b: this._rgba.b + (percent / 100), a: this._rgba.a}
        )    
    }

    /** Returns a duplicate color with the specified alpha component */
    withAlpha(newAlpha) {
        return new SimpleColor({r: this._rgba.r, g: this._rgba.g, b: this._rgba.b, a: newAlpha})
    }

    /** Checks if a SimpleColor is equal to this one */
    equalTo(sColor) {
        var rgb = sColor.rgb
        return (this._rgba.r === rgb.r && this._rgba.g === rgb.g && this._rgba.b === rgb.b && this._rgba.a === rgb.a)
    }

    // -------------------------------------------------------------------
    // Static Methods
    // -------------------------------------------------------------------

    /** A pure black color */
    static get black() {
        return new SimpleColor({r: 0, g: 0, b: 0, a: 1})
    }

    /** A pure white color */
    static get white() {
        return new SimpleColor({r: 1, g: 1, b: 1, a: 1})
    }

    // -------------------------------------------------------------------

    /** Tries to get the rgba values from a css rgb() or rgba() string */
    static getRgbFromString(str) {
        var regex = /rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)?(?:\s*,\s*(\d+\.\d+|\d+)\s*\))?/mi
        var match = str.match(regex);
        
        if (match) {
            var r = match[1], g = match[2], b = match[3], a = match[4]
            function checkMatch(m) {
                var num = parseFloat(m)
                return isNaN(num) ? 1 : num
            }

            return { r: checkMatch(r) / 255, g: checkMatch(g) / 255, b: checkMatch(b) / 255, a: checkMatch(a)}
        }

        return null
    }

    /** Converts a 3, 6, or 8 digit hex string into an RGBA color */
    static hexToRgb(hex) {
        var a, b, g, r, u

        if (hex.match(/^#?([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/)) {
            if (hex.length === 4 || hex.length === 7) {
                hex = hex.substr(1)
            }

            if (hex.length === 3) {
                hex = hex.split("");
                hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
            }

            u = parseInt(hex, 16);
            r = u >> 16;
            g = u >> 8 & 0xFF;
            b = u & 0xFF;
            return {r: r / 255, g: g / 255, b: b / 255, a: 1}
        }

        if (hex.match(/^#?([A-Fa-f0-9]{8})$/)) {
            if (hex.length === 9) {
                hex = hex.substr(1)
            }

            u = parseInt(hex, 16);
            r = u >> 24 & 0xFF;
            g = u >> 16 & 0xFF;
            b = u >> 8 & 0xFF;
            a = (u & 0xFF) / 0xFF * 100 / 100

            return {r: r / 255, g: g / 255, b: b / 255, a: a}
        }

        return null
    }

    /** Converts a HSV object to a RGB */
    static hsvToRgb(hsv) {
        var h = hsv.h
        var s = hsv.s
        var v = hsv.v
        var a = hsv.hasOwnProperty('a') ? hsv.a : 1

        var r, g, b, i, f, p, q, t

        i = Math.floor(h * 6)
        f = h * 6 - i
        p = v * (1 - s)
        q = v * (1 - f * s)
        t = v * (1 - (1 - f) * s)

        switch (i % 6) {
            case 0: r = v, g = t, b = p; break;
            case 1: r = q, g = v, b = p; break;
            case 2: r = p, g = v, b = t; break;
            case 3: r = p, g = q, b = v; break;
            case 4: r = t, g = p, b = v; break;
            case 5: r = v, g = p, b = q; break;
        }

        return {r: r, g: g, b: b, a: a}
    }

    /** Converts an RGB object to a HSV */
    static rgbToHsv(rgb) {
        var r = rgb.r, g = rgb.g, b = rgb.b, a = rgb.a

        var max = Math.max(r, g, b), min = Math.min(r, g, b);
        var h, s, v = max;

        var d = max - min;
        s = max === 0 ? 0 : d / max;

        if (max === min) {
            h = 0;
        } else {
            switch (max) {
                case r: h = (g - b) / d + (g < b ? 6 : 0); break;
                case g: h = (b - r) / d + 2; break;
                case b: h = (r - g) / d + 4; break;
            }

            h /= 6;
        }

        return {h: h, s: s, v: v, a: a}
    }

    // -------------------------------------------------------------------
    // Private Helpers
    // -------------------------------------------------------------------

    /**
     * Prevents the saturation and brightness values from being zero.
     * This prevents colors from losing their hue and saturation values when being converted to rgb.
     */
    _safeHSVColor(hsv) {
        hsv.s = hsv.s == 0 ? 0.000001 : hsv.s
        hsv.v = hsv.v == 0 ? 0.000001 : hsv.v

        return hsv
    }

    /** Makes sure all rgba values are present, are a number, and are between 0-1 */
    _safeRGB(rgb) {
        var _this = this
        function check(n) {
            if (isNaN(n)) return 1
            return _this._clamp(n, 0, 1)
        }

        return {r: check(rgb.r), g: check(rgb.g), b: check(rgb.b), a: check(rgb.a)}
    }

    /** Ridiculously complex in order to be accurate. Thanks javascript: https://stackoverflow.com/a/12830454 */
    _roundToPlaces(num, scale) {
        if(!("" + num).includes("e")) {
            return +(Math.round(num + "e+" + scale)  + "e-" + scale);
        } else {
            var arr = ("" + num).split("e");
            var sig = ""
            if(+arr[1] + scale > 0) {
                sig = "+";
            }
            return +(Math.round(+arr[0] + "e" + sig + (+arr[1] + scale)) + "e-" + scale);
        }
    }

    /** Clamps a value between two numbers */
    _clamp(value, min, max) {
        return Math.min(Math.max(value, min), max)
    }

    /** Returns true if an object has all the keys in the specified array */
    _objectHasKeys(object, keys) {
        for(var i = 0; i < keys.length; i++){
            if (!object.hasOwnProperty(keys[i])) {
                return false
            }
        }

        return true
    }

    _isObject(val) {
        if (val == null) return false
        return (typeof val === 'function' || typeof val === 'object')
    }

    // -------------------------------------------------------------------
}
