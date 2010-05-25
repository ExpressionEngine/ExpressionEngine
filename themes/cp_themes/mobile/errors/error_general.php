<!doctype html>
<html>
  <head>
    <meta charset="UTF-8" />
    <title>Error | ExpressionEngine</title>
    <link rel="apple-touch-icon" href="" />
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

}body {
    background: rgb(0,0,0);
}

body > * {
    background: rgb(197,204,211) url(../themes/cp_themes/mobile/images/pinstripes.png);
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
    background: url(../themes/cp_themes/mobile/images/toolbar.png) #6d84a2 repeat-x;
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
    -webkit-border-image: url(../themes/cp_themes/mobile/images/toolButton.png) 0 5 0 5;
}

.button.active, .back.active, .cancel.active, .add.active {
    -webkit-border-image: url(<../themes/cp_themes/mobile/images/toolButton.png) 0 5 0 5;	
}

.blueButton {
    -webkit-border-image: url(../themes/cp_themes/mobile/images/actionButton.png) 0 5 0 5;
    border-width: 0 5px;
}

.back {
    left: 6px;
    right: auto;
    padding: 0;
    max-width: 55px;
    border-width: 0 8px 0 14px;
    -webkit-border-image: url(../themes/cp_themes/mobile/images/backButton.png) 0 8 0 14;
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

.grayButton {
    -webkit-border-image: url(../themes/cp_themes/mobile/images/grayButton.png) 0 12 0 12;
    color: #FFFFFF;
}

/* @end */

/* @group Lists */

h1 + ul, h2 + ul, h3 + ul, h4 + ul, h5 + ul, h6 + ul {
    margin-top: 0;
}

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
-->
</style>

</head>
<body>
<div id="error" class="current">
    <div class="toolbar">
        <h1>Error</h1>
		<a href="javascript:history.go(-1);" class="back"><?php echo lang('back')?></a>
    </div>
	
	<div class="container pad">

		<h3><?php echo $heading; ?></h3>
		<?php echo $message; ?>
	</div>

	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
	<script charset="utf-8" type="text/javascript" src="<?php echo BASE.AMP.'C=javascript'.AMP.'M=load'?>"></script>
	<script type="text/javascript">
	/* <![CDATA[ */
	var jQT = new $.jQTouch({
	    addGlossToIcon: true,
	});
	/* ]]> */	
	</script>
</body>
</html>