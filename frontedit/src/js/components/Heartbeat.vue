<template>
	<div ref="ee-pro-heartbeat" class="ee-pro-heartbeat-ee-44E4F0E59DFA295EB450397CA40D1169 popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window--active" :style="computedStyles">
		<div v-if="!sessionActive" class="popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169 small centered">
			<header class="popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-header">
				<h3 class="popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-header--title">Login</h3>
				<a class="popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-header--close" v-on:click="closeLogin">
					<img v-bind:src="storeHost +'img/modal-close.svg'" alt="Close Window" />
				</a>
			</header>
			<div class="popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__body">
				<div class="popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__body--content with-iframe">
					<iframe
						:ref="'ee-pro-auth-modal'"
						frameBorder="0"
						v-if="iframeSrc"
						:src="iframeSrc"
					></iframe>
				</div>
			</div>
			<footer class="popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-footer">
				<div class="popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-footer--link--wrapper">
					<a class="popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-footer--button-primary" v-on:click="onSubmit"
					>{{ loginLang }}</a>
				</div>
			</footer>
		</div>
	</div>
</template>

<script>
	export default {
		props: {},
		data() {
			return {
				isShown: false,
				zIndex: 0,
				heartbeatInterval: null,
				timeSinceMovement: 0,
				minutesForTimeout: 60,
				secondsForTimeout: 60, // We keep this variable for shorter testing periods
				iframeSrc: null,
				loginLang: null,
				lastMovement: new Date()
			}
		},
		methods: {
			closeLogin() {
				location.reload()
			},
			getZIndex() {
				if(!this.isShown) {
					this.zIndex = -1
					return
				}
				this.zIndex = 123123123123
			},
			onSubmit() {
				this.$refs['ee-pro-auth-modal'].contentWindow.postMessage({type: 'eeproprocessreauth'})
			},
			runHeartbeat() {
				this.timeSinceMovement++
				// If it's been minutesForTimeout * secondsForTimeout minutes since movement
				// Or, if the date is the same from last date, in case of time out where browsers reset
				const now = new Date()

				let diff =(now.getTime() - this.lastMovement.getTime()) / 1000
				diff /= (this.minutesForTimeout * this.secondsForTimeout)
				const finalDiff = Math.abs(Math.round(diff))

				if(this.timeSinceMovement >= this.minutesForTimeout * this.secondsForTimeout || finalDiff >= 1) {
					this.iframeSrc = this.loginUrl
					this.isShown = true
					this.getZIndex()
					clearInterval(this.heartbeatInterval)
					this.$store.commit('setSessionActive', false)
				}
			},
			resetActivity() {
				this.timeSinceMovement = 0
				this.lastMovement = new Date()
			},
			startHeartbeat() {
				this.heartbeatInterval = setInterval(() => this.runHeartbeat(), 1000)
			},
			init() {
				let self = this
				document.addEventListener('keydown', () => this.resetActivity())
				document.addEventListener('mousedown', () => this.resetActivity())
				document.addEventListener('mousemove', () => this.resetActivity())
				window.addEventListener('message', function(callbackevent) {
					if(callbackevent.data && callbackevent.data.type && callbackevent.data.type == 'eereauthenticate') {
						self.$store.commit('setSessionActive', true)
						self.isShown = false
						self.resetActivity()
						self.startHeartbeat()
					}
				})
				this.startHeartbeat()

				let lang = window?.EE?.pro?.lang

				if(!lang) {
					lang = {}
				}

				this.loginLang = lang.login || 'Login'
			},

		},
		computed: {
            storeHost() {
                return this.$store.state.themesUrl
            },
			computedStyles() {
				return {
					'z-index': this.zIndex,
					opacity: this.isShown ? 1 : 0.2,
					display: this.isShown ? 'block' : 'none'
				}
			},
			loginUrl() {
				return this.$store.state.loginUrl
			},
			sessionActive() {
				return this.$store.state.sessionActive
			}
		},
		mounted() {
			this.getZIndex()
			this.init()
		}
	};
</script>
<style lang="scss" scoped>
.ee-pro-heartbeat-ee-44E4F0E59DFA295EB450397CA40D1169 {
	position: fixed;
	top: 0;
	right: 0;
	bottom: 0;
	left: 0;
	width: 100%;
	height: 100%;
	animation-name: fadeIn;
	animation-duration: .5s;
	animation-timing-function: ease-in-out;
	animation-delay: 0;
	animation-direction: alternate;
	animation-iteration-count: 1;
	animation-fill-mode: none;
	animation-play-state: running;
	background-color: #f7f7fb !important;

		.popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169 {
			font-family: -apple-system, BlinkMacSystemFont, segoe ui, helvetica neue, helvetica, Cantarell, Ubuntu, roboto, noto, arial, sans-serif;
			width: 800px !important;
			min-width: 320px !important;
			max-width: 100% !important;
			height: 500px !important;
			max-height: 80vh !important;
			background-color: #f7f7fb !important;
			border: 2px solid #cbcbda !important;
			border-radius: 6px !important;
			-webkit-box-shadow: 0 3px 15px 0 rgba(0,0,0,.15) !important;
			-moz-box-shadow: 0 3px 15px 0 rgba(0,0,0,.15) !important;
			box-shadow: 0 3px 15px 0 rgba(0,0,0,.15) !important;
			resize: both !important;
			overflow: auto !important;
			position: fixed !important;
			z-index: 10000 !important;
			-webkit-font-smoothing: antialiased !important;
			// transition: height 0.2s, width 0.2s;

			&.small {
				width: 375px !important;
				height: 320px !important;
			}

			&.large {
				width: 800px !important;
			}

			&.centered {
				margin: auto !important;
				position: relative !important;
				top: 30px !important;
				left: unset !important;
			}

			&.full-screen {
				top: 0 !important;
				left: 0 !important;
				bottom: 0 !important;
				right: 0 !important;
				height: 100vh !important;
				width: 100vw !important;
			}

			&__window-header {
				background-color: #f7f7fb !important;
				padding: 12px 15px 13px !important;
				position: relative !important;
				cursor: default !important;
				border-radius: 6px 6px 0 0 !important;
				border-bottom: 1px solid #dfe0ef !important;

				&--title {
					font-family: -apple-system, BlinkMacSystemFont, segoe ui, helvetica neue, helvetica, Cantarell, Ubuntu, roboto, noto, arial, sans-serif !important;
					margin: 0 !important;
					text-transform: uppercase !important;
					letter-spacing: 1px !important;
					font-size: 13px !important;
					line-height: 20px !important;
					font-weight: 500 !important;
					color: #8f90b0 !important;
					opacity: 0.5 !important;
				}

				&--max {
					position: absolute !important;
					right: 35px !important;
					top: 50% !important;
					transform: translateY(-50%) !important;
					color: #8f90b0 !important;
					cursor: pointer !important;
					i, svg {
						width: 10px !important;
					}
				}

				&--close {
					width: 22px !important;
					height: 22px !important;
					padding: 0 !important;
					position: absolute !important;
					right: 10px !important;
					top: calc(50% - 1px) !important;
					transform: translateY(-50%) !important;
					color: #8f90b0 !important;
					cursor: pointer !important;
					border-radius: 1000px !important;
					-webkit-transition: background-color .15s ease-in-out !important;
					-moz-transition: background-color .15s ease-in-out !important;
					-o-transition: background-color .15s ease-in-out !important;
					-webkit-user-select: none !important;
					-moz-user-select: none !important;
					-ms-user-select: none !important;
					opacity: 0.5 !important;

					&:hover {
						background-color: rgba(143, 144, 176, 0.15) !important;
					}

					img {
						width: 10px !important;
						position: absolute !important;
						top: 50% !important;
						left: 0 !important;
						right: 0 !important;
						margin: auto !important;
						transform: translateY(-50%) !important;
					}

					.svg-inline--fa {
						width: 10px !important;
					}
				}
			}

			&__window-footer {
				background-color: #f7f7fb !important;
				padding: 12px 15px !important;
				position: absolute !important;
				left: 0 !important;
				bottom: 0 !important;
				right: 0 !important;
				border-radius: 0 0 6px 6px !important;
				border-top: 1px solid #dfe0ef !important;
				display: flex !important;
				flex-wrap: nowrap !important;

				&--button {
					margin-right: 5px !important;
					padding: 5px 15px !important;
					max-width: fit-content !important;
					font-size: 13px !important;
					line-height: 1.5 !important;
					align-self: center !important;
					border-radius: 4px !important;
					display: inline-block !important;
					flex-grow: 1 !important;
					text-align: center !important;
					vertical-align: middle !important;
					touch-action: manipulation !important;
					background-image: none !important;
					cursor: pointer !important;
					border: 1px solid transparent !important;
					white-space: nowrap !important;
					-webkit-transition: background-color .15s ease-in-out !important;
					-moz-transition: background-color .15s ease-in-out !important;
					-o-transition: background-color .15s ease-in-out !important;
					-webkit-user-select: none !important;
					-moz-user-select: none !important;
					-ms-user-select: none !important;
					text-decoration: none !important;

					&-primary {
						@extend .popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-footer--button;
						color: #ffffff !important;
						background-color: #5d63f1 !important;
						border-color: #5d63f1 !important;
						font-weight: 500 !important;

					&:focus,
						&.focus {
							color: #fff !important;
							background-color: #2e36ed !important;
							border-color: #2e36ed !important;
						}
						&:hover {
							color: #fff !important;
							background-color: #2e36ed !important;
								border-color: #2e36ed !important;
						}
						&:active,
						&.active {
							color: #fff !important;
							background-color: #2e36ed !important;
								border-color: #2e36ed !important;

							&:hover,
							&:focus,
							&.focus {
							color: #fff !important;
							background-color: #2e36ed !important;
								border-color: #2e36ed !important;
							}
						}
						&:active,
						&.active {
							background-image: none !important;
						}
					}

					&-default {
						@extend .popup-modal-ee-44E4F0E59DFA295EB450397CA40D1169__window-footer--button;
						color: #0d0d19 !important;
						background-color: #ffffff !important;
						border-color: #cbcbda !important;
						font-weight: 400 !important;

						&:focus,
						&.focus {
							color: #0d0d19 !important;
							background-color: #f7f7fb !important;
							border-color: #cbcbda !important;
						}
						&:hover {
							color: #0d0d19 !important;
							background-color: #f7f7fb !important;
								border-color: #cbcbda !important;
						}
						&:active,
						&.active {
							color: #0d0d19 !important;
							background-color: #f7f7fb !important;
								border-color: #cbcbda !important;

							&:hover,
							&:focus,
							&.focus {
							color: #0d0d19 !important;
							background-color: #f7f7fb !important;
								border-color: #cbcbda !important;
							}
						}
						&:active,
						&.active {
							background-image: none !important;
						}
					}
				}

				&--link {
					padding: 5px 0px 0px 10px !important;
					font-size: 13px !important;
					line-height: 1.5 !important;
					flex-grow: 8 !important;
					text-align: right !important;
					display: block !important;
					color: #5d63f1 !important;
					cursor: pointer !important;
					text-decoration: none !important;
					transition: color .15s ease-in-out !important;
				}
			}

			&__body {
				height: calc(100% - 102px) !important;
				&--content {
					line-height: 1.6 !important;
					margin: 0 !important;
					padding: 15px 20px !important;
					font-weight: inherit !important;

					&.with-iframe {
						height: 100% !important;
						padding: 0 !important;

						iframe {
							height: 100% !important;
							width: 100% !important;
							overflow: auto !important;
						}
					}
				}
			}

			&.show {
				opacity: 1 !important;
				pointer-events: auto !important;
			}

			&__close {
				position: absolute !important;
				font-size: 1.2rem !important;
				right: 10px !important;
				top: 10px !important;
				cursor: pointer !important;
			}
			&::-webkit-scrollbar {
				width: 2px !important;
				height: 2px !important;
			}
			&::-webkit-scrollbar-button {
				width: 8px !important;
				height: 8px !important;
			}
			&::-webkit-scrollbar-thumb {
				background: #e1e1e1 !important;
				border: 0px none #ffffff !important;
				border-radius: 50px !important;
			}
			&::-webkit-scrollbar-thumb:hover {
				background: #ffffff !important;
			}
			&::-webkit-scrollbar-thumb:active {
				background: #000000 !important;
			}
			&::-webkit-scrollbar-track {
				background: #666666 !important;
				border: 0px none #ffffff !important;
				border-radius: 50px !important;
			}
			&::-webkit-scrollbar-track:hover {
				background: #666666 !important;
			}
			&::-webkit-scrollbar-track:active {
				background: #333333 !important;
			}
			&::-webkit-scrollbar-corner {
				background: transparent !important;
			}

		}
}
</style>