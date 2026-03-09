require.config({
    paths: {
        "simditormin": "addons/simditor/js/simditor.min",
    },
    shim: {
        'simditormin': ['css!addons/simditor/css/simditor.css'],
    }
})
require(['jquery'], function ($) {
    if ($('.editor', document).length>0) {
        require(['simditormin'], function (undefined) {
        })
    }
})