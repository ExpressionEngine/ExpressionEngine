<template>
	<div class="inner-dock-ee-44E4F0E59DFA295EB450397CA40D1169" :style="computedStyles">
		<slot></slot>
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
		computed: {
			computedStyles() {
				const styles = {}

				if(this.maxWidth) {
					styles.maxWidth = `${this.maxWidth}px`
				}

				if(this.minWidth) {
					styles.minWidth = `${this.minWidth}px`
				}

				return styles
			}
		}
	};
</script>