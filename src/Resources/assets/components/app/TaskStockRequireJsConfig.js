var TaskStockRequireJsConfig = {
    paths: {
        'tinymce': 'taskstock/js/tinymce/tinymce.min',
        'typeahead': 'taskstock/js/typeahead/typeahead.jquery.min',
        'bloodhound': 'taskstock/js/typeahead/bloodhound.min'
    },
    shim: {
        'tinymce': {
            exports: 'tinymce'
        },
        'typeahead': {
            deps: ['jquery'],
            init: function ($) {
                return require.s.contexts._.registry['typeahead.js'].factory( $ );
            }
        },
        'bloodhound': {
            deps: ['jquery'],
            exports: 'Bloodhound'
        }
    }
};