/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

interface RGB {
    r: number, g: number, b: number, a: number
}

interface HSV {
    h: number, s: number, v: number, a: number
}

/**
 *  A class for basic color conversion, manipulation, and formatting.
 */
class SimpleColor {

    isValid: boolean = false
    initValue: string

    private _rgba: RGB = { r: 1, g: 1, b: 1, a: 1 }

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
    constructor(color: any) {

        this.initValue = color

        const foundColor = (rgba: RGB) => {
            this._rgba   = this._safeRGB(rgba)
            this.isValid = true
        }

        if (typeof color === 'string')
        {
            color = color.trim()

            var fromHex = SimpleColor.hexToRgb(color)

            if (fromHex != null) {
                foundColor(fromHex)
            }
            else {
                var rgb = SimpleColor.getRgbFromString(color)
                if (rgb != null)
                    foundColor(rgb)
            }
        }

        else if (this._isObject(color))
        {
            if (this._objectHasKeys(color, ['r', 'g', 'b']))
                foundColor(color)

            else if (this._objectHasKeys(color, ['h', 's', 'v']))
                foundColor(SimpleColor.hsvToRgb(this._safeHSVColor(color)))
        }
    }

    // -------------------------------------------------------------------
    // Public
    // -------------------------------------------------------------------

    /** Returns the RGB values of this color */
    get rgb(): RGB {
        return this._rgba
    }

    /** Returns the RGB 255 values of this color */
    get rgb255(): RGB {
        return { r: Math.round(this._rgba.r * 255), g: Math.round(this._rgba.g * 255), b: Math.round(this._rgba.b * 255), a: this._rgba.a }
    }

    /** Returns the HSV values of this color */
    get hsv(): HSV {
        return SimpleColor.rgbToHsv(this._rgba)
    }

    /** The perceived brightness of this color represented in a 0 to 1 value */
    get brightness(): number {
        var rgb255 = this.rgb255
        return (rgb255.r * 299 + rgb255.g * 587 + rgb255.b * 114) / 1000
    }

    /** Returns a hex string representing this color */
    get hexStr(): string {
        var rgb255 = this.rgb255

        const componentToHex = (c: number) => {
            var hex = c.toString(16)
            return hex.length == 1 ? '0' + hex : hex
        }

        return '#' + componentToHex(rgb255.r) + componentToHex(rgb255.g) + componentToHex(rgb255.b)
    }

    /** Returns a css rgba() string representing this color */
    get rgbaStr(): string {
        var rgb255 = this.rgb255
        return 'rgba(' + rgb255.r + ', ' + rgb255.g + ', ' + rgb255.b + ', ' + (+this._rgba.a.toFixed(2)) + ')'
    }

    /** Returns true or false if this color is considered dark */
    isDark(): boolean {
        return this.brightness < 130
    }

    /** Returns a black or white color in contrast to this color */
    fullContrastColor(): SimpleColor {
        return this.isDark() ? SimpleColor.white : SimpleColor.black
    }

    /** Darkens or lightens the color with a -100 to 100 percentage */
    shade(percent: number): SimpleColor {
        return new SimpleColor(
            {r: this._rgba.r + (percent / 100), g: this._rgba.g + (percent / 100), b: this._rgba.b + (percent / 100), a: this._rgba.a}
        )
    }

    /** Returns a new color with the specified alpha component */
    withAlpha(newAlpha: number): SimpleColor {
        return new SimpleColor(Object.assign({}, this.rgb, { a: newAlpha }))
    }

    /** Returns a new color with any specified hsv components   */
    withHsv(newHsv: { h?: number, s?: number, v?: number }): SimpleColor {
        return new SimpleColor(Object.assign({}, this.hsv, newHsv))
    }

    /** Returns a new color with any specified rgb components    */
    withRgb(newRgb: { r?: number, g?: number, b?: number, a?: number }): SimpleColor {
        return new SimpleColor(Object.assign({}, this.rgb, newRgb))
    }

    /** Checks if a SimpleColor is equal to this one */
    equalTo(sColor: SimpleColor): boolean {
        var rgb = sColor.rgb
        return (this._rgba.r === rgb.r && this._rgba.g === rgb.g && this._rgba.b === rgb.b && this._rgba.a === rgb.a)
    }

    // -------------------------------------------------------------------
    // Static
    // -------------------------------------------------------------------

    /** A pure black color */
    static get black(): SimpleColor {
        return new SimpleColor({r: 0, g: 0, b: 0, a: 1})
    }

    /** A pure white color */
    static get white(): SimpleColor {
        return new SimpleColor({r: 1, g: 1, b: 1, a: 1})
    }

    /** Tries to get the rgba values from a css rgb() or rgba() string */
    static getRgbFromString(str: string): RGB {
        var regex = /rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)?(?:\s*,\s*(\d+\.\d+|\d+)\s*\))?/mi
        var match = str.match(regex);

        if (match) {
            var r = match[1], g = match[2], b = match[3], a = match[4]
            const checkMatch = (m: any) => {
                var num = parseFloat(m)
                return isNaN(num) ? 1 : num
            }

            return { r: checkMatch(r) / 255, g: checkMatch(g) / 255, b: checkMatch(b) / 255, a: checkMatch(a)}
        }

        return null
    }

    /** Converts a 3, 6, or 8 digit hex string into an RGBA color */
    static hexToRgb(hex: string): RGB {
        var a, b, g, r, u

        if (hex.match(/^#?([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/)) {
            if (hex.length === 4 || hex.length === 7) {
                hex = hex.substr(1)
            }

            if (hex.length === 3) {
                let hSplit = hex.split("");
                hex = hSplit[0] + hSplit[0] + hSplit[1] + hSplit[1] + hSplit[2] + hSplit[2];
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
    static hsvToRgb(hsv: HSV): RGB {
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
    static rgbToHsv(rgb: RGB): HSV {
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
    // Private
    // -------------------------------------------------------------------

    /**
     * Prevents the saturation and brightness values from being zero.
     * This prevents colors from losing their hue and saturation values when being converted to rgb.
     */
    private _safeHSVColor(hsv: HSV): HSV {
        hsv.s = hsv.s == 0 ? 0.000001 : hsv.s
        hsv.v = hsv.v == 0 ? 0.000001 : hsv.v

        return hsv
    }

    /** Makes sure all rgba values are present, are a number, and are between 0-1 */
    private _safeRGB(rgb: RGB): RGB {
        const check = (n: any) => {
            if (isNaN(n))
                return 1

            // Clamp the value
            return Math.min(Math.max(n, 0), 1)
        }

        return { r: check(rgb.r), g: check(rgb.g), b: check(rgb.b), a: check(rgb.a) }
    }

    /** Returns true if an object has all the keys in the specified array */
    private _objectHasKeys(object: object, keys: string[]): boolean {
        var objKeys = Object.keys(object)
        return keys.some(v => objKeys.indexOf(v) !== -1)
    }

    private _isObject(val: any): boolean {
        if (val == null) {
            return false
        }

        return (typeof val === 'function' || typeof val === 'object')
    }
}
