var TaskStockRequireJsConfig = {
    paths: {
        'taskstock_tinymce': 'taskstock/js/tinymce/tinymce.min',
        'taskstock_typeahead': 'taskstock/js/typeahead/typeahead.jquery.min',
        'taskstock_bloodhound': 'taskstock/js/typeahead/bloodhound.min'
    },
    shim: {
        'taskstock_tinymce': {
            exports: 'tinymce'
        },
        'taskstock_typeahead': {
            deps: ['jquery'],
            init: function ($) {
                return require.s.contexts._.registry['typeahead.js'].factory( $ );
            }
        },
        'taskstock_bloodhound': {
            deps: ['jquery'],
            exports: 'Bloodhound'
        }
    }
};