const Encore = require('@symfony/webpack-encore');

Encore
// the project directory where all compiled assets will be stored
    .setOutputPath('assets/compiled/')
    // the public path used by the web server to access the previous directory
    .setPublicPath('/assets/compiled')

    .addEntry('base', './assets/js/base.js')
    .addEntry('admin', './assets/js/admin.js')
    .addEntry('captcha', './assets/js/captcha.js')

    .addExternals({
        grecaptcha: 'grecaptcha',
        jquery: 'jQuery',
        jqueryui: 'jQuery'
    })

    // fixes modules that expect jQuery to be global
    //.autoProvidejQuery()

    .enableBuildNotifications()

    .disableSingleRuntimeChunk()
;
// export the final configuration
module.exports = Encore.getWebpackConfig();
