<template>
	<div class="dock-item-ee-44E4F0E59DFA295EB450397CA40D1169 dock-item-ee-44E4F0E59DFA295EB450397CA40D1169---prev-next-button cursor-pointer-ee-44E4F0E59DFA295EB450397CA40D1169">
		<img v-bind:src="storeHost +'img/caret-left.svg'" alt="Left Caret" />
	</div>
</template>

<script>
	export default {
        computed: {
            storeHost() {
                return this.$store.state.themesUrl
            }
        }
	}
</script>