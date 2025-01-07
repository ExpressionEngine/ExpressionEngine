<template>
    <div class="cursor-move-ee-44E4F0E59DFA295EB450397CA40D1169" v-on:dblclick="toggleCollapsed">
        <img v-if="storeHost" v-bind:src="storeHost +'img/drag-handle.svg'" class="svg-ee-44E4F0E59DFA295EB450397CA40D1169 ee-pro-drag-handle-ee-44E4F0E59DFA295EB450397CA40D1169" v-bind:alt="lang"/>
    </div>
</template>

<script>
    export default {
        computed: {
            storeHost() {
                return this.$store.state.themesUrl
            },
            lang() {
                return this.$store.state.lang.drag_handle_icon
            },
        },
        methods: {
            toggleCollapsed() {
                if (window.getSelection) {
                    if (window.getSelection().empty) {
                        window.getSelection().empty();
                    } else if (window.getSelection().removeAllRanges) {
                        window.getSelection().removeAllRanges();
                    }
                } else if (document.selection) {
                    document.selection.empty();
                }
                this.$store.dispatch('toggleCollapsed')
            }
        }
    };
</script>