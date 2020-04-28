<style type="text/css">
@-webkit-keyframes zoomIn {
  from {
    opacity: 0;
    -webkit-transform: scale3d(0.3, 0.3, 0.3);
    transform: scale3d(0.3, 0.3, 0.3);
  }

  50% {
    opacity: 1;
  }
}

@keyframes zoomIn {
  from {
    opacity: 0;
    -webkit-transform: scale3d(0.3, 0.3, 0.3);
    transform: scale3d(0.3, 0.3, 0.3);
  }

  50% {
    opacity: 1;
  }
}

@keyframes heartBeat {
	from {
		opacity: 0;
		-webkit-transform: scale3d(0.3, 0.3, 0.3);
		transform: scale3d(0.3, 0.3, 0.3);
	}

	10% {
		opacity: 1;
		transform: scale(1);
	}

	15% {
		transform: scale(1);
	}
	16% {
		transform: scale(1.1);
	}
	17% {
		transform: scale(1);
	}

	25% {
		transform: scale(1);
	}
	26% {
		transform: scale(1.1);
	}
	27% {
		transform: scale(1);
	}

	35% {
		transform: scale(1);
	}
	36% {
		transform: scale(1.1);
	}
	37% {
		transform: scale(1);
	}

	45% {
		transform: scale(1);
	}
	46% {
		transform: scale(1.1);
	}
	47% {
		transform: scale(1);
	}

	55% {
		transform: scale(1);
	}
	56% {
		transform: scale(1.1);
	}
	57% {
		transform: scale(1);
	}

	65% {
		transform: scale(1);
	}
	66% {
		transform: scale(1.1);
	}
	67% {
		transform: scale(1);
	}

	75% {
		transform: scale(1);
	}
	76% {
		transform: scale(1.1);
	}
	77% {
		transform: scale(1);
	}

	85% {
		transform: scale(1);
	}
	86% {
		transform: scale(1.1);
	}
	87% {
		transform: scale(1);
	}

	95% {
		transform: scale(1);
	}
	96% {
		transform: scale(1.1);
	}
	97% {
		transform: scale(1);
	}
}

.ee-wrapper-overflow, .ee-main__content, .container, .dashboard {
	width: 100%;
	height: 100%;
}
#welcome-screen {
	display: flex;
	align-items: center;
	justify-content: center;
	color: #fff;
	text-align: center;
}
.welcome-splash {
	max-width: 50%;
}
.zoomIn {
	-webkit-animation-name: zoomIn;
	animation-name: zoomIn;
	animation-fill-mode: both;
}
.zoomIn.logo {
	animation-delay: 3s;
	animation-duration: 3s;
}
.zoomIn.welcome {
	animation-delay: 6s;
	animation-duration: 3s;
}
.zoomIn.shortcut {
	display: inline-block;
	animation-name: heartBeat;
	animation-delay: 9s;
	animation-duration: 30s;
	/* animation-duration: 3s; */
	font-size: 40px;
	padding: 0 20px;
    margin-top: 25px;
	/* border-radius: 25px; */
	/* background-color: rgba(255, 255, 255, 0.2) */
}
.zoomIn.cta {
	/* animation-timing-function: ease-in-out; */
}
.zoomIn.choices {
	animation-delay: 34s;
	animation-duration: 3s;
	position: absolute;
	left: 50%;
	bottom: 20px;
	width: 400px;
	margin-left: -200px;

}
.zoomIn.choices .btn:first-child {
	margin-right: 20px;
}
.choices p {
	line-height: 3em;
}
.ee-main--dashboard {
	background: var(--ee-sidebar-bg);
}

.welcome-jump-instructions {
	display: none;
	position: absolute;
	top: 0;
	left: 0;
    width: 100%;
    height: 100%;
}
.info-element {
	position: fixed;
	z-index: 110;
}
#ji-title {
	top: 25px;
	left: 0;
	right: 0;
	color: #fff;
	font-size: 19px;
	text-align: center;
}
#ji-one-arrow {
	top: 60px;
    left: 50%;
    width: 100px;
    height: 100px;
    margin-left: -380px;
}
#ji-one {
    top: 151px;
    left: 50%;
    width: 175px;
    height: 100px;
    margin-left: -464px;
    color: #fff;
	font-size: 20px;
}
#ji-two-arrow {
	top: 275px;
    left: 50%;
    width: 100px;
    height: 100px;
    margin-left: 282px;
    transform: scaleX(-1);
}
#ji-two {
    top: 360px;
    left: 50%;
    width: 250px;
    height: 100px;
    margin-left: 282px;
    color: #fff;
	font-size: 20px;
}
#ji-three-arrow {
	top: 405px;
    left: 50%;
    width: 75px;
    height: 75px;
    margin-left: -357px;
}
#ji-three {
    top: 470px;
    left: 50%;
    width: 190px;
    height: 100px;
    margin-left: -459px;
    color: #fff;
    font-size: 16px;
}
#ji-four-arrow {
	top: 8px;
	right: 80px;
	width: 75px;
	height: 75px;
}
#ji-four {
	top: 75px;
    right: 80px;
    width: 190px;
    height: 100px;
    margin-left: -459px;
    color: #fff;
    font-size: 16px;
}
.key-ctrl {
	font-size: 34px;
    background-color: rgba(255, 255, 255, 0.4);
    padding: 0px 10px;
    border-radius: 8px;
}
.shortcut > span {
	background-color: rgba(255, 255, 255, 0.3);
    border-radius: 11px;
    font-size: 33px;
    padding: 0 13px;
    vertical-align: text-top;
}
.main-nav__account {
	position: fixed;
	top: 30px;
	right: 40px;
}
.modal-wrap {
	top: 80px;
}
</style>

<div class="dashboard__item dashboard__item--full color: var(--ee-text-normal);" id="welcome-screen" style="display: none">
	<div class="welcome-splash">
		<svg class="zoomIn logo" xmlns="http://www.w3.org/2000/svg" width="148.157" height="126.181" viewBox="0 0 38.157 26.181"><defs><style>.a{fill:#ffffff;}</style></defs><g transform="translate(-175.171 -12.013)"><path class="a" d="M68.174,225.238a11.006,11.006,0,0,1-2.59,1.979,5.335,5.335,0,0,1-2.3.534,3.019,3.019,0,0,1-2.283-.9,3.533,3.533,0,0,1-.856-2.533l.074-1.089.151-.026a27.272,27.272,0,0,0,5.95-1.524,6.794,6.794,0,0,0,2.774-1.97,3.716,3.716,0,0,0,.84-2.218,2.676,2.676,0,0,0-1.023-2.164,4.53,4.53,0,0,0-2.961-.867,10.486,10.486,0,0,0-4.976,1.227,9.452,9.452,0,0,0-3.636,3.352,8.3,8.3,0,0,0-1.327,4.436,5.39,5.39,0,0,0,1.568,4.075,6.052,6.052,0,0,0,4.334,1.5,8.73,8.73,0,0,0,3.66-.777,11.565,11.565,0,0,0,3.319-2.5ZM60.4,222.122a12.165,12.165,0,0,1,2.07-5.29,2.918,2.918,0,0,1,2.28-1.381,1.4,1.4,0,0,1,1.092.5,1.954,1.954,0,0,1,.425,1.323,4.61,4.61,0,0,1-1.713,3.473,8.12,8.12,0,0,1-3.931,1.6l-.273.05Z" transform="translate(130.676 -196.872)"/><path class="a" d="M31.787,219.629c-1.508-5.9,2.063-12.07,8.2-14.885-.154.055-.3.118-.449.175a4.081,4.081,0,0,1,.4-.173l-11.49,2.964,3.063,1.813c-4.012,3.4-6.082,7.608-5.089,11.491,1.553,6.079,10.056,9.249,19.687,7.691C39.382,229.207,33.294,225.53,31.787,219.629Z" transform="translate(149 -190.905)"/><path class="a" d="M87.7,209.482c1.508,5.9-2.062,12.071-8.2,14.886.153-.056.3-.119.449-.176a3.845,3.845,0,0,1-.4.174l11.49-2.964-3.063-1.812c4.011-3.4,6.081-7.61,5.089-11.492-1.553-6.078-10.057-9.249-19.687-7.689C80.107,199.905,86.2,203.582,87.7,209.482Z" transform="translate(120.011 -188)"/></g></svg>

		<h4 class="zoomIn welcome">Welcome to EE 6 Alpha</h4>
		<div class="zoomIn shortcut cta"><span class="jump-trigger"></span> <span>J</span></div>

		<?=form_open(ee('CP/URL')->make('homepage/set-viewmode'))?>
		<div class="zoomIn choices">
			<p><em>How would you like to proceed?</em></p>
			<p>
				<?=form_button(['name' => 'ee_cp_viewmode', 'value' => 'jumpmenu', 'type' => 'submit'], "Jump Menu only", 'class="btn button--action" data-submit-text="Proceed with Jump Menu only" data-work-text="Please wait..."')?>
				<?=form_button(['name' => 'ee_cp_viewmode', 'value' => 'classic', 'type' => 'submit'], "Jump Menu &amp; Navigation", 'class="btn button--action" data-submit-text="Proceed with Jump Menu and Main Navigation" data-work-text="Please wait..."')?>
			</p>
		</div>
		<?=form_close()?>
	</div>
</div>

<div class="welcome-jump-instructions">
	<div id="ji-title" class="info-element">Introducing the Jump Menu</div>

	<svg id="ji-one-arrow" class="info-element" data-name="Arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="white" d="M506.48,246.45,287.45,120a11,11,0,0,0-16.55,9.56v53h0C269.35,182.68,116,204,8.66,350.8L2,360.38a11,11,0,0,0,14.67,15.81l10-5.91c1.91-1.14,6.05-3.48,6.3-3.62,57.85-34.53,114.85-52,169.42-52,40.84,0,65.37,10.26,68.52,11.63v56.24A11,11,0,0,0,287.45,392l219-126.47a11,11,0,0,0,0-19.11Z"/></svg>
	<div id="ji-one" class="info-element">
		Start typing where you want to go
	</div>

	<svg id="ji-two-arrow" class="info-element" data-name="Arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="white" d="M506.48,246.45,287.45,120a11,11,0,0,0-16.55,9.56v53h0C269.35,182.68,116,204,8.66,350.8L2,360.38a11,11,0,0,0,14.67,15.81l10-5.91c1.91-1.14,6.05-3.48,6.3-3.62,57.85-34.53,114.85-52,169.42-52,40.84,0,65.37,10.26,68.52,11.63v56.24A11,11,0,0,0,287.45,392l219-126.47a11,11,0,0,0,0-19.11Z"/></svg>
	<div id="ji-two" class="info-element">
		Results will show up here.<br /><br />
		You can use the keyboard to select a result and press [Enter] to activate it.
	</div>

	<svg id="ji-three-arrow" class="info-element" data-name="Arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="white" d="M506.48,246.45,287.45,120a11,11,0,0,0-16.55,9.56v53h0C269.35,182.68,116,204,8.66,350.8L2,360.38a11,11,0,0,0,14.67,15.81l10-5.91c1.91-1.14,6.05-3.48,6.3-3.62,57.85-34.53,114.85-52,169.42-52,40.84,0,65.37,10.26,68.52,11.63v56.24A11,11,0,0,0,287.45,392l219-126.47a11,11,0,0,0,0-19.11Z"/></svg>
	<div id="ji-three" class="info-element">
		Some results show a <em>[bracketed]</em> option, which will require a secondary input.
	</div>

	<svg id="ji-four-arrow" class="info-element" data-name="Arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="white" d="M506.48,246.45,287.45,120a11,11,0,0,0-16.55,9.56v53h0C269.35,182.68,116,204,8.66,350.8L2,360.38a11,11,0,0,0,14.67,15.81l10-5.91c1.91-1.14,6.05-3.48,6.3-3.62,57.85-34.53,114.85-52,169.42-52,40.84,0,65.37,10.26,68.52,11.63v56.24A11,11,0,0,0,287.45,392l219-126.47a11,11,0,0,0,0-19.11Z"/></svg>
	<div id="ji-four" class="info-element">
		You can toggle the navigation or jump menu from here at any time.
	</div>
</div>