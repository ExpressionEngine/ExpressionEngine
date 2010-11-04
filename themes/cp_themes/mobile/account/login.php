<!doctype html>
<html>
  <head>
    <meta charset="UTF-8" />
    <title><?=$cp_page_title?> | ExpressionEngine</title>
    <link rel="apple-touch-icon" href="" />
	<script charset="utf-8" type="text/javascript" src="<?=$this->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT=jquery';?>"></script>
	<script type="application/x-javascript" src="<?=$cp_theme_url?>javascript/jqtouch.js"></script>
<style type="text/css">
<!--
* {
    margin: 0;
    padding: 0;
}
a {
    -webkit-tap-highlight-color: rgba(0,0,0,0);
}

body {
    overflow-x: hidden;
    -webkit-user-select: none;
    -webkit-text-size-adjust: none;
    font-family: Helvetica;
    -webkit-perspective: 800;
    -webkit-transform-style: preserve-3d;
}
.selectable, input, textarea {
    -webkit-user-select: auto;
}
body > * {
    -webkit-backface-visibility: hidden;
    -webkit-box-sizing: border-box;
    display: none;
    position: absolute;
    left: 0;
    width: 100%;
    -webkit-transform: translate3d(0,0,0) rotate(0) scale(1);
    min-height: 420px !important;
}
body.fullscreen > * {
    min-height: 460px !important;
}
body.fullscreen.black-translucent > * {
    min-height: 480px !important;
}
body.landscape > * {
    min-height: 320px;
}
body > .current {
    display: block !important;
}

.in, .out {
	-webkit-animation-timing-function: ease-in-out;
	-webkit-animation-duration: 350ms;
}

.slide.in {
	-webkit-animation-name: slideinfromright;
}

.slide.out {
	-webkit-animation-name: slideouttoleft;
}

.slide.in.reverse {
	-webkit-animation-name: slideinfromleft;
}

.slide.out.reverse {
	-webkit-animation-name: slideouttoright;
}

@-webkit-keyframes slideinfromright {
    from { -webkit-transform: translateX(100%); }
    to { -webkit-transform: translateX(0); }
}

@-webkit-keyframes slideinfromleft {
    from { -webkit-transform: translateX(-100%); }
    to { -webkit-transform: translateX(0); }
}

@-webkit-keyframes slideouttoleft {
    from { -webkit-transform: translateX(0); }
    to { -webkit-transform: translateX(-100%); }
}

@-webkit-keyframes slideouttoright {
    from { -webkit-transform: translateX(0); }
    to { -webkit-transform: translateX(100%); }
}

@-webkit-keyframes fadein {
    from { opacity: 0; }
    to { opacity: 1; }
}

@-webkit-keyframes fadeout {
    from { opacity: 1; }
    to { opacity: 0; }
}

.fade.in {
	z-index: 10;
	-webkit-animation-name: fadein;
}
.fade.out {
	z-index: 0;
}

.dissolve.in {
	-webkit-animation-name: fadein;
}

.dissolve.out {
	-webkit-animation-name: fadeout;	
}



.flip {
	-webkit-animation-duration: .65s;
}

.flip.in {
	-webkit-animation-name: flipinfromleft;
}

.flip.out {
	-webkit-animation-name: flipouttoleft;
}

/* Shake it all about */

.flip.in.reverse {
	-webkit-animation-name: flipinfromright;
}

.flip.out.reverse {
	-webkit-animation-name: flipouttoright;
}

@-webkit-keyframes flipinfromright {
    from { -webkit-transform: rotateY(-180deg) scale(.8); }
    to { -webkit-transform: rotateY(0) scale(1); }
}

@-webkit-keyframes flipinfromleft {
    from { -webkit-transform: rotateY(180deg) scale(.8); }
    to { -webkit-transform: rotateY(0) scale(1); }
}

@-webkit-keyframes flipouttoleft {
    from { -webkit-transform: rotateY(0) scale(1); }
    to { -webkit-transform: rotateY(-180deg) scale(.8); }
}

@-webkit-keyframes flipouttoright {
    from { -webkit-transform: rotateY(0) scale(1); }
    to { -webkit-transform: rotateY(180deg) scale(.8); }
}

.slideup.in {
	-webkit-animation-name: slideup;
	z-index: 10;
}

.slideup.out {
	-webkit-animation-name: dontmove;
	z-index: 0;
}

.slideup.out.reverse {
	z-index: 10;
	-webkit-animation-name: slidedown;
}

.slideup.in.reverse {
	z-index: 0;
	-webkit-animation-name: dontmove;
}


@-webkit-keyframes slideup {
    from { -webkit-transform: translateY(100%); }
    to { -webkit-transform: translateY(0); }
}

@-webkit-keyframes slidedown {
    from { -webkit-transform: translateY(0); }
    to { -webkit-transform: translateY(100%); }
}



/* Hackish, but reliable. */

@-webkit-keyframes dontmove {
    from { opacity: 1; }
    to { opacity: 1; }
}

.swap {
	-webkit-transform: perspective(800);
	-webkit-animation-duration: .7s;
}
.swap.out {
	-webkit-animation-name: swapouttoleft;
}
.swap.in {
	-webkit-animation-name: swapinfromright;
}
.swap.out.reverse {
	-webkit-animation-name: swapouttoright;
}
.swap.in.reverse {
	-webkit-animation-name: swapinfromleft;
}


@-webkit-keyframes swapouttoright {
    0% {
        -webkit-transform: translate3d(0px, 0px, 0px) rotateY(0deg);
        -webkit-animation-timing-function: ease-in-out;
    }
    50% {
        -webkit-transform: translate3d(-180px, 0px, -400px) rotateY(20deg);
        -webkit-animation-timing-function: ease-in;
    }
    100% {
        -webkit-transform:  translate3d(0px, 0px, -800px) rotateY(70deg);
    }
}

@-webkit-keyframes swapouttoleft {
    0% {
        -webkit-transform: translate3d(0px, 0px, 0px) rotateY(0deg);
        -webkit-animation-timing-function: ease-in-out;
    }
    50% {
        -webkit-transform:  translate3d(180px, 0px, -400px) rotateY(-20deg);
        -webkit-animation-timing-function: ease-in;
    }
    100% {
        -webkit-transform: translate3d(0px, 0px, -800px) rotateY(-70deg);
    }
}

@-webkit-keyframes swapinfromright {
    0% {
        -webkit-transform: translate3d(0px, 0px, -800px) rotateY(70deg);
        -webkit-animation-timing-function: ease-out;
    }
    50% {
        -webkit-transform: translate3d(-180px, 0px, -400px) rotateY(20deg);
        -webkit-animation-timing-function: ease-in-out;
    }
    100% {
        -webkit-transform: translate3d(0px, 0px, 0px) rotateY(0deg);
    }
}

@-webkit-keyframes swapinfromleft {
    0% {
        -webkit-transform: translate3d(0px, 0px, -800px) rotateY(-70deg);
        -webkit-animation-timing-function: ease-out;
    }
    50% {
        -webkit-transform: translate3d(180px, 0px, -400px) rotateY(-20deg);
        -webkit-animation-timing-function: ease-in-out;
    }
    100% {
        -webkit-transform: translate3d(0px, 0px, 0px) rotateY(0deg);
    }
}

.cube {
    -webkit-animation-duration: .55s;
}

.cube.in {
	-webkit-animation-name: cubeinfromright;
    -webkit-transform-origin: 0% 50%;
}
.cube.out {
	-webkit-animation-name: cubeouttoleft;
    -webkit-transform-origin: 100% 50%;
}
.cube.in.reverse {
	-webkit-animation-name: cubeinfromleft;	
    -webkit-transform-origin: 100% 50%;
}
.cube.out.reverse {
	-webkit-animation-name: cubeouttoright;
    -webkit-transform-origin: 0% 50%;

}

@-webkit-keyframes cubeinfromleft {
	from {
        -webkit-transform: rotateY(-90deg) translateZ(320px);
        opacity: .5;
	}
    to {
        -webkit-transform: rotateY(0deg) translateZ(0);
        opacity: 1;
    }
}
@-webkit-keyframes cubeouttoright {
    from {
        -webkit-transform: rotateY(0deg) translateX(0);
        opacity: 1;
    }
    to {
        -webkit-transform: rotateY(90deg) translateZ(320px);
        opacity: .5;
    }
}
@-webkit-keyframes cubeinfromright {
    from {
        -webkit-transform: rotateY(90deg) translateZ(320px);
        opacity: .5;
    }
    to {
        -webkit-transform: rotateY(0deg) translateZ(0);
        opacity: 1;
    }
}
@-webkit-keyframes cubeouttoleft {
    from {
        -webkit-transform: rotateY(0deg) translateZ(0);
        opacity: 1;
    }
    to {
        -webkit-transform: rotateY(-90deg) translateZ(320px);
        opacity: .5;
    }
}




.pop {
	-webkit-transform-origin: 50% 50%;
}

.pop.in {
	-webkit-animation-name: popin;
	z-index: 10;
}

.pop.out.reverse {
	-webkit-animation-name: popout;
	z-index: 10;
}

.pop.in.reverse {
	z-index: 0;
	-webkit-animation-name: dontmove;
}

@-webkit-keyframes popin {
    from {
        -webkit-transform: scale(.2);
        opacity: 0;
    }
    to {
        -webkit-transform: scale(1);
        opacity: 1;
    }
}

@-webkit-keyframes popout {
    from {
        -webkit-transform: scale(1);
        opacity: 1;
    }
    to {
        -webkit-transform: scale(.2);
        opacity: 0;
    }
}body {
    background: rgb(0,0,0);
}

body > * {
    background: rgb(197,204,211) url(<?=$cp_theme_url?>images/pinstripes.png);
}

h1, h2 {
    font: bold 18px Helvetica;
    text-shadow: rgba(255,255,255,.2) 0 1px 1px;
    color: rgb(76, 86, 108);
    margin: 10px 20px 6px;
}

/* @group Toolbar */

.toolbar {
    -webkit-box-sizing: border-box;
    border-bottom: 1px solid #2d3642;
    padding: 10px;
    height: 45px;
    background: url(<?=$cp_theme_url?>images/toolbar.png) #6d84a2 repeat-x;
    position: relative;
}

.black-translucent .toolbar {
	margin-top: 20px;
}

.toolbar > h1 {
    position: absolute;
    overflow: hidden;
    left: 50%;
    top: 10px;
    line-height: 1em;
    margin: 1px 0 0 -75px;
    height: 40px;
    font-size: 20px;
    width: 150px;
    font-weight: bold;
    text-shadow: rgba(0, 0, 0, 0.4) 0px -1px 0;
    text-align: center;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #fff;
}

body.landscape .toolbar > h1 {
    margin-left: -125px;
    width: 250px;
}

.button, .back, .cancel, .add {
    position: absolute;
    overflow: hidden;
    top: 8px;
    right: 6px;
    margin: 0;
    border-width: 0 5px;
    padding: 0 3px;
    width: auto;
    height: 30px;
    line-height: 30px;
    font-family: inherit;
    font-size: 12px;
    font-weight: bold;
    color: #fff;
    text-shadow: rgba(0, 0, 0, 0.5) 0px -1px 0;
    text-overflow: ellipsis;
    text-decoration: none;
    white-space: nowrap;
    background: none;
    -webkit-border-image: url(<?=$cp_theme_url?>images/toolButton.png) 0 5 0 5;
}

.button.active, .back.active, .cancel.active, .add.active {
    -webkit-border-image: url(<?=$cp_theme_url?>images/toolButton.png) 0 5 0 5;	
}

.blueButton {
    -webkit-border-image: url(<?=$cp_theme_url?>images/actionButton.png) 0 5 0 5;
    border-width: 0 5px;
}

.back {
    left: 6px;
    right: auto;
    padding: 0;
    max-width: 55px;
    border-width: 0 8px 0 14px;
    -webkit-border-image: url(<?=$cp_theme_url?>images/backButton.png) 0 8 0 14;
}

.leftButton, .cancel {
    left: 6px;
    right: auto;
}

.add {
    font-size: 24px;
    line-height: 24px;
    font-weight: bold;
}

.whiteButton,
.grayButton {
    display: block;
    border-width: 0 12px;
    padding: 10px;
    text-align: center;
    font-size: 20px;
    font-weight: bold;
    text-decoration: inherit;
    color: inherit;
}

.whiteButton {
    -webkit-border-image: url(<?=$cp_theme_url?>images/whiteButton.png) 0 12 0 12;
    text-shadow: rgba(255, 255, 255, 0.7) 0 1px 0;
}

.grayButton {
    -webkit-border-image: url(<?=$cp_theme_url?>images/grayButton.png) 0 12 0 12;
    color: #FFFFFF;
}

/* @end */

/* @group Lists */

h1 + ul, h2 + ul, h3 + ul, h4 + ul, h5 + ul, h6 + ul {
    margin-top: 0;
}

ul {
    color: black;
    background: #fff;
    border: 1px solid #B4B4B4;
    font: bold 17px Helvetica;
    padding: 0;
    margin: 15px 10px 17px 10px;
    -webkit-border-radius: 8px;
}

ul li {
    color: #666;
    border-top: 1px solid #B4B4B4;
    list-style-type: none;
    padding: 10px 10px 10px 10px;
}

/* when you have a first LI item on any list */

li:first-child, li:first-child a {
    border-top: 0;
    -webkit-border-top-left-radius: 8px;
    -webkit-border-top-right-radius: 8px;
}

li:last-child, li:last-child a {
    -webkit-border-bottom-left-radius: 8px;
    -webkit-border-bottom-right-radius: 8px;
}

/* universal arrows */

ul li.arrow {
    background-image: url(<?=$cp_theme_url?>images/chevron.png);
    background-position: right center;
    background-repeat: no-repeat;
}

ul li.arrow a { 
	padding: 12px 22px 12px 10px;
}

#plastic ul li.arrow, #metal ul li.arrow {
    background-image: url(<?=$cp_theme_url?>images/chevron_dg.png);
    background-position: right center;
    background-repeat: no-repeat;
}

/* universal links on list */

ul li a, li.img a + a {
    color: #000;
    text-decoration: none;
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
    display: block;
    padding: 12px 10px 12px 10px;
    margin: -10px;
    -webkit-tap-highlight-color: rgba(0,0,0,0);
}

ul li a.active {
    background: #194fdb url(<?=$cp_theme_url?>images/selection.png) 0 0 repeat-x;
    color: #fff;
}

ul li a.active.loading {
    background-image: url(<?=$cp_theme_url?>images/loading.gif);
    background-position: 95% center;
    background-repeat: no-repeat;
}

ul li a.button {
    background-color: #194fdb;
    color: #fff;
}

ul li.img a + a {
    margin: -10px 10px -20px -5px;
    font-size: 17px;
    font-weight: bold;
}

ul li.img a + a + a {
    font-size: 14px;
    font-weight: normal;
    margin-left: -10px;
    margin-bottom: -10px;
    margin-top: 0;
}

ul li.img a + small + a {
    margin-left: -5px;
}

ul li.img a + small + a + a {
    margin-left: -10px;
    margin-top: -20px;
    margin-bottom: -10px;
    font-size: 14px;
    font-weight: normal;
}

ul li.img a + small + a + a + a {
    margin-left: 0px !important;
    margin-bottom: 0;
}

ul li a + a {
    color: #000;
    font: 14px Helvetica;
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
    display: block;
    margin: 0;
    padding: 0;
}

ul li a + a + a, ul li.img a + a + a + a, ul li.img a + small + a + a + a {
    color: #666;
    font: 13px Helvetica;
    margin: 0;
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
    display: block;
    padding: 0;
}

/*
@end */

/* @group Forms */

ul.form li {
    padding: 7px 10px;
}

ul.form li.error {
    border: 2px solid red;
}

ul.form li.error + li.error {
    border-top: 0;
}

ul.form li:hover {
    background: #fff;
}

ul li input[type="text"], ul li input[type="password"], ul li input[type="tel"], ul li textarea, ul li select {
    color: #777;
    background: #fff url(../.png);
    border: 0;
    font: normal 17px Helvetica;
    padding: 0;
    display: inline-block;
    margin-left: 0px;
    width: 100%;
    -webkit-appearance: textarea;
}

ul li textarea {
    height: 120px;
    padding: 0;
    text-indent: -2px;
}

ul li select {
    text-indent: 0px;
    background: transparent url(<?=$cp_theme_url?>images/chevron.png) no-repeat 103% 3px;
    -webkit-appearance: textfield;
    margin-left: -6px;
    width: 104%;
}

ul li input[type="checkbox"], ul li input[type="radio"] {
    margin: 0;
    color: rgb(50,79,133);
    padding: 10px 10px;
}

ul li input[type="checkbox"]:after, ul li input[type="radio"]:after {
    content: attr(title);
    font: 17px Helvetica;
    display: block;
    width: 246px;
    margin: -12px 0 0 17px;
}

/* @end */

/* @group Edge to edge */

.edgetoedge h4 {
    color: #fff;
    background: rgb(154,159,170) url(<?=$cp_theme_url?>images/listGroup.png) top left repeat-x;
    border-top: 1px solid rgb(165,177,186);
    text-shadow: #666 0 1px 0;
    margin: 0;
    padding: 2px 10px;
}

.edgetoedge, .metal {
    margin: 0;
    padding: 0;
    background-color: rgb(255,255,255);
}

.edgetoedge ul, .metal ul, .plastic ul {
    -webkit-border-radius: 0;
    margin: 0;
    border-left: 0;
    border-right: 0;
    border-top: 0;
}

.metal ul {
    border-top: 0;
    border-bottom: 0;
    background: rgb(180,180,180);
}

.edgetoedge ul li:first-child, .edgetoedge ul li:first-child a, .edgetoedge ul li:last-child, .edgetoedge ul li:last-child a, .metal ul li:first-child a, .metal ul li:last-child a {
    -webkit-border-radius: 0;
}

.edgetoedge ul li small {
    font-size: 16px;
    line-height: 28px;
}

.edgetoedge li, .metal li {
    -webkit-border-radius: 0;
}

.edgetoedge li em {
    font-weight: normal;
    font-style: normal;
}

.edgetoedge h4 + ul {
    border-top: 1px solid rgb(152,158,164);
    border-bottom: 1px solid rgb(113,125,133);
}

/* @end */

/* @group Mini Label */

ul li small {
    color: #369;
    font: 17px Helvetica;
    text-align: right;
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
    display: block;
    width: 23%;
    float: right;
    padding: 3px 0px;
}

ul li.arrow small {
    padding: 0 15px;
}

ul li small.counter {
    font-size: 17px !important;
    line-height: 13px !important;
    font-weight: bold;
    background: rgb(154,159,170);
    color: #fff;
    -webkit-border-radius: 11px;
    padding: 4px 10px 5px 10px;
    display: inline !important;
    width: auto;
    margin-top: -22px;
}

ul li.arrow small.counter {
    margin-right: 15px;
}

/* @end */

/* @group Plastic */

#plastic ul li.arrow, #metal ul li.arrow {
    background-image: url(<?=$cp_theme_url?>images/listArrow.png);
    background-position: right center;
    background-repeat: no-repeat;
}

.edgetoedge ul, .metal ul, .plastic ul {
    -webkit-border-radius: 0;
    margin: 0;
    border-left: 0;
    border-right: 0;
    border-top: 0;
}

.metal ul li {
    border-top: 1px solid rgb(238,238,238);
    border-bottom: 1px solid rgb(156,158,165);
    background: url(<?=$cp_theme_url?>images/bgMetal.png) top left repeat-x;
    font-size: 26px;
    text-shadow: #fff 0 1px 0;
}

.metal ul li a {
    line-height: 26px;
    margin: 0;
    padding: 13px 0;
}

.metal ul li a:hover {
    color: rgb(0,0,0);
}

.metal ul li:hover small {
    color: inherit;
}

.metal ul li a em {
    display: block;
    font-size: 14px;
    font-style: normal;
    color: #444;
    width: 50%;
    line-height: 14px;
}

.metal ul li small {
    float: right;
    position: relative;
    margin-top: 10px;
    font-weight: bold;
}

.metal ul li.arrow a small {
    padding-right: 0;
    line-height: 17px;
}

.metal ul li.arrow {
    background: url(<?=$cp_theme_url?>images/bgMetal.png) top left repeat-x,
    url(<?=$cp_theme_url?>images/chevron_dg.png) right center no-repeat;
}

.plastic {
    margin: 0;
    padding: 0;
    background: rgb(173,173,173);
}

.plastic ul {
    -webkit-border-radius: 0;
    margin: 0;
    border-left: 0;
    border-right: 0;
    border-top: 0;
    background-color: rgb(173,173,173);
}

.plastic ul li {
    -webkit-border-radius: 0;
    border-top: 1px solid rgb(191,191,191);
    border-bottom: 1px solid rgb(157,157,157);
}

.plastic ul li:nth-child(odd) {
    background-color: rgb(152,152,152);
    border-top: 1px solid rgb(181,181,181);
    border-bottom: 1px solid rgb(138,138,138);
}

.plastic ul + p {
    font-size: 11px;
    color: #2f3237;
    text-shadow: none;
    padding: 10px 10px;
}

.plastic ul + p strong {
    font-size: 14px;
    line-height: 18px;
    text-shadow: #fff 0 1px 0;
}

.plastic ul li a {
    text-shadow: rgb(211,211,211) 0 1px 0;
}

.plastic ul li:nth-child(odd) a {
    text-shadow: rgb(191,191,191) 0 1px 0;
}

.plastic ul li small {
    color: #3C3C3C;
    text-shadow: rgb(211,211,211) 0 1px 0;
    font-size: 13px;
    font-weight: bold;
    text-transform: uppercase;
    line-height: 24px;
}

#plastic ul.minibanner, #plastic ul.bigbanner {
    margin: 10px;
    border: 0;
    height: 81px;
    clear: both;
}

#plastic ul.bigbanner {
    height: 140px !important;
}

#plastic ul.minibanner li {
    border: 1px solid rgb(138,138,138);
    background-color: rgb(152,152,152);
    width: 145px;
    height: 81px;
    float: left;
    -webkit-border-radius: 5px;
    padding: 0;
}

#plastic ul.bigbanner li {
    border: 1px solid rgb(138,138,138);
    background-color: rgb(152,152,152);
    width: 296px;
    height: 140px;
    float: left;
    -webkit-border-radius: 5px;
    padding: 0;
    margin-bottom: 4px;
}

#plastic ul.minibanner li:first-child {
    margin-right: 6px;
}

#plastic ul.minibanner li a {
    color: transparent;
    text-shadow: none;
    display: block;
    width: 145px;
    height: 81px;
}

#plastic ul.bigbanner li a {
    color: transparent;
    text-shadow: none;
    display: block;
    width: 296px;
    height: 145px;
}

/* @end */

/* @group Individual */

ul.individual {
    border: 0;
    background: none;
    clear: both;
    overflow: hidden;
}

ul.individual li {
    color: rgb(183,190,205);
    background: white;
    border: 1px solid rgb(180,180,180);
    font-size: 14px;
    text-align: center;
    -webkit-border-radius: 8px;
    -webkit-box-sizing: border-box;
    width: 48%;
    float: left;
    display: block;
    padding: 11px 10px 14px 10px;
}

ul.individual li + li {
    float: right;
}

ul.individual li a {
    color: rgb(50,79,133);
    line-height: 16px;
    margin: -11px -10px -14px -10px;
    padding: 11px 10px 14px 10px;
    -webkit-border-radius: 8px;
}

ul.individual li a:hover {
    color: #fff;
    background: #36c;
}

/* @end */

/* @group Toggle */


.toggle {
    width: 94px;
    position: relative;
    height: 27px;
    display: block;
    overflow: hidden;
    float: right;
}

.toggle input[type="checkbox"]:checked {
    left: 0px;
}

.toggle input[type="checkbox"] {
    -webkit-tap-highlight-color: rgba(0,0,0,0);
    margin: 0;
    -webkit-border-radius: 5px;
    background: #fff url(<?=$cp_theme_url?>images/on_off.png) 0 0 no-repeat;
    height: 27px;
    overflow: hidden;
    width: 149px;
    border: 0;
    -webkit-appearance: textarea;
    background-color: transparent;
    -webkit-transition: left .15s;
    position: absolute;
    top: 0;
    left: -55px;
}
/* @end */

.info {
    background: #dce1eb;
    font-size: 12px;
    line-height: 16px;
    text-align: center;
    text-shadow: rgba(255,255,255,.8) 0 1px 0;
    color: rgb(76, 86, 108);
    padding: 15px;
    border-top: 1px solid rgba(76, 86, 108, .3);
    font-weight: bold;
}


.container {
  color: black;
  background: #fff;
  border: 1px solid #B4B4B4;
  font: bold 17px Helvetica;
  padding: 0;
  margin: 15px 10px 17px 10px;
  -webkit-border-radius: 8px;
}

.pad {
	padding:5%;
}

table.mainTable {
	text-align:			left;
	background:#fff;
	-webkit-border-radius: 8px;
  padding: 0;
  margin: 15px 10px 17px 10px;
}

table.mainTable th {
	background:			rgb(76, 86, 108);
	color:				#fff;
	padding:			10px 8px 6px 8px;
	border-left:		1px solid #45555f;
	cursor:				pointer;
	white-space:		nowrap;
}

table.mainTable thead {
	background-color:#000;
}

table.mainTable thead tr {
	-webkit-border-top-radius:8px;	
}

table.mainTable td {
	padding:			5px 8px;
	background-color:	#fff;
	border-bottom:		1px solid #d0d7df;
	border-left:		1px solid #d0d7df;
}

table.mainTable td.title {
	text-align:			left;
	font-weight:		bold;
}

table.mainTable tr.odd td, table#controllers tr.odd td {
	background-color:	#ECF1F4;
}

table.mainTable td.id {
	border-left:		0;
}
-->
</style>

</head>
<div id="home" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
    </div>
    <?php if ($message != ''):?>
    <div class='info'><?=$message?></div>
    <?php endif;?>
    <?=form_open('C=login'.AMP.'M=authenticate', array('class' => 'panel', 'title' => 'Login'), array('return_path' => $return_path))?>
		<ul id="home" class="rounded">
			<li>
				<?=form_input(array(
								'size' 			=> '35', 
								'name' 			=> 'username', 
								'id' 			=> 'username', 
								'value' 		=> $username, 
								'maxlength' 	=> 50, 
								'placeholder' 	=> lang('username')))?>
			</li>
			<li>
				<?=form_password(array(
								'size' 			=> '20',
								'name' 			=> 'password', 
								'id' 			=> 'password', 
								'maxlength' 	=> 40,
								'placeholder'	=> lang('password')))?>
			</li>

			<li>
				<a href='<?=BASE.AMP.'C=login'.AMP.'M=forgotten_password_form'?>'>(Forgot your password?)</a>
			</li>
			
			<li>
			    <?php if ($this->config->item('admin_session_type') == 'c'):?>
				    <?=form_checkbox('remember_me', '1')?>
				    <?=lang('remember_me')?>
				<?php endif;?>
			</li>

		</ul>
        <?=form_submit('submit', lang('login'), 'class="whiteButton"')?>
    </form>
    </div>
	<script type="text/javascript">
	/* <![CDATA[ */
	var jQT = new $.jQTouch({
	    addGlossToIcon: true,
	});
	/* ]]> */	
	</script>
</body>
</html>