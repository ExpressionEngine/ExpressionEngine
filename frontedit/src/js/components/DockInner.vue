<template>
	<div
		class="inner-dock-ee-44E4F0E59DFA295EB450397CA40D1169"
		:style="computedStyles"
		ref="dockInner"
	>
		<div class="inner-dock-container-ee-44E4F0E59DFA295EB450397CA40D1169" ref="innerdockcontainer">
			<dock-inner-prev-button
				v-if="hasSteps && showPrev"
				ref="dockPrev"
				v-on:click.native="prevNextStep(-1)"
				style="position: absolute;left:0"
				:style="computedContainerStyles"
			/>
			<dock-item
				v-for="item in customDockItems"
				:key="item.id"
				:actionId="item.id"
				:icon="item.icon"
				:method="item.method"
				:methodData="item.methodData"
				:title="item.title"
				:color="item.color"
				:backgroundColor="item.backgroundColor"
				:borderColor="item.borderColor"
				:borderWidth="item.borderWidth"
				:borderStyle="item.borderStyle"
				:hoverBackgroundColor="item.hoverBackgroundColor"
				:hoverBorderColor="item.hoverBorderColor"
				:hoverBorderWidth="item.hoverBorderWidth"
				:hoverBorderStyle="item.hoverBorderStyle"
				:iconColor="item.iconColor"
				:image="item.image"
				:no-button="item.noButton"
				:always-shown="item.alwaysShown"
				:as-prolet="item.modalAlwaysShown || true"
				:additional-classes="item.additionalClasses"
			/>
			<dock-inner-next-button
				v-if="hasSteps && showNext"
				ref="dockNext"
				v-on:click.native="prevNextStep(1)"
				style="position: absolute;right:0"
			/>
		</div>
	</div>
</template>

<script>
	export default {
		props: {
			maxWidth: {
				type: Number
			},
			minWidth: {
				type: Number
			}
		},
		data() {
			return {
				offset: 0,
				hasSteps: false,
				containerWidth: 0
			}
		},
		methods: {
			prevNextStep(step = 1) {
				if (window.getSelection) {
					if (window.getSelection().empty) {
						window.getSelection().empty();
					} else if (window.getSelection().removeAllRanges) {
						window.getSelection().removeAllRanges();
					} else if (document.selection) {
						document.selection.empty();
					}
				}

				const maxOffset = this.$refs.innerdockcontainer.offsetWidth

				this.offset += step * 50

				if(this.offset < 0) {
					this.offset = 0
				}

				if(this.offset > maxOffset) {
					this.offset = maxOffset
				}

				this.$refs.innerdockcontainer.scroll(this.offset, 0)
			},
			setHasSteps() {
				const dockInner = this.$refs.dockInner,
						innerdockcontainer = this.$refs.innerdockcontainer;

				if(dockInner && innerdockcontainer) {
					const dockInnerWidth = dockInner.offsetWidth,
							innerdockcontainerWidth = innerdockcontainer.scrollWidth
					this.hasSteps = dockInnerWidth < innerdockcontainerWidth
					return
				}

				this.hasSteps = false
			}
		},
		computed: {
			customDockItems() {
				return this.$store.state.customDockItems
			},
			showPrev() {
				return this.offset > 0
			},
			showNext() {
				if(!this.$refs.innerdockcontainer) {
					return false
				}
				const maxOffset = this.$refs.innerdockcontainer.offsetWidth - 20
				return this.offset < maxOffset
			},
			computedStyles() {
				const styles = {}

				if(this.maxWidth) {
					styles.maxWidth = `${this.maxWidth}px`
				}

				if(this.minWidth) {
					styles.minWidth = `${this.minWidth}px`
				}

				return styles
			},
			computedContainerStyles() {
				const styles = {}
				styles.minWidth = 'initial'
				return styles
			}
		},
		mounted() {
			setTimeout(() => {
				this.setHasSteps()
				this.containerWidth
			}, 200)

			window.addEventListener('resize', () => {
				this.setHasSteps()
			})
		}
	};
</script>