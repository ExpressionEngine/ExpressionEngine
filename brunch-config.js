// See http://brunch.io for documentation.
module.exports = {
    files: {
        javascripts: {
            joinTo: {
                'vendor.js': /^(?!cp-styles\/app)/,
                'main.min.js': /^cp-styles\/app/
            }
        },
        stylesheets: {
            joinTo: {
                'common.min.css': [
                    'cp-styles/app/styles/main.scss',
                    'cp-styles/app/styles/legacy/legacy.less',
                    'cp-styles/app/styles/css.css',
                ],
                'eecms-debug.min.css': [
                    'cp-styles/app/styles/debugger.scss'
                ]
                // 'main.min.css':
            }
        }
    },

    paths: {
        public: './cp-styles/build',
        watched: ['cp-styles/app', 'cp-styles/vendor']
    },

    plugins: {
        // babel: {
        //     presets: ['latest']
        // },
        sass: {
            // mode: 'ruby'
        },
        brunchTypescript: {
            removeComments: true
        },
        cleancss: {
            // inline: ['all'],
            keepSpecialComments: 0,
            removeEmpty: true,
            level: 1
        }
    }
};
