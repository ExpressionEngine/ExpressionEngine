module.exports = {
	outputDir: './src/js/build',
	pages: {
		index: {
			entry: './src/js/main.js',
			template: './src/js/public/index.html',
		}
	},
	configureWebpack: {
		optimization: {
			splitChunks: false
		}
	},
	css: {
		extract: false,
	}
}