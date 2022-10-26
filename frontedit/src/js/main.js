import Vue from 'vue'
import App from './App.vue'
import store from './store'

Vue.config.productionTip = false

const files = require.context('./', true, /\.vue$/i)
files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default))

// Tooltip
import VTooltip from 'v-tooltip'
Vue.use(
	VTooltip,
	{
		defaultTemplate: '<div class="ee-pro-tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>',
	}
)

// Toggle
import OnoffToggle from 'vue-onoff-toggle'
Vue.use(OnoffToggle)

// Directives
import mustRemainOnScreen from './directives/mustRemainOnScreen'
import ClickOutside from './directives/ClickOutside'
Vue.directive('must-remain-on-screen', mustRemainOnScreen)
Vue.directive('click-outside', ClickOutside)

// Uncomment for debugging
// Vue.config.devtools = true

new Vue({
	store,
	render: h => h(App)
}).$mount('#ee-44E4F0E59DFA295EB450397CA40D1169')