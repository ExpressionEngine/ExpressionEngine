<template>
	<div class="toggle-item-ee-44E4F0E59DFA295EB450397CA40D1169" v-show="showItem" v-tooltip.top-center="langTitle">
		<onoff-toggle
			v-model="toggledValue"
			theme="default"
			:height="26"
			:width="48"
      :margin="3"
			:thumbColor="thumbColor"
			offColor="#cbcbda"
			onColor="#5d63f1"
      :shadow="false"
		/>
	</div>
</template>

<script>
	export default {
		props: {
			fxn: {
				type: String,
				required: true
			},
			toggledVariable: {
				type: String,
				required: true
			},
			thumbColor: {
				type: String,
				default: '#ffffff !important'
			},
			borderColor: {
				type: String,
				default: '#5d63f1'
			},
			alwaysShown: {
				type: Boolean,
				default: false
			}
		},
		methods: {
		},
		computed: {
			langTitle() {
				return this.$store.state.lang.edit_mode || 'Edit Mode'
			},
			toggledValue: {
				set(val) {
					const commitString = 'set' + this.toggledVariable.charAt(0).toUpperCase() + this.toggledVariable.slice(1)
					this.$store.commit(commitString, val)
					this.$store.dispatch('toggleEditModeEnabled', true)
				},
				get() {
					return this.$store.state[this.toggledVariable]
				}
			},
			toggledValueColor() {
				return this.$store.state[this.toggledVariable] ? "#ffffff" : '#5d63f1'
			},
			collapsed() {
				return this.$store.state.collapsed
			},
			showItem() {
				return this.collapsed
						? this.alwaysShown
						: true
			}
		}
	};
</script>