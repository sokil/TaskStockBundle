// RequireJs
requirejs.config({
    baseUrl: '/',
    paths: {
        tinymce: 'js/tinymce/tinymce.min',
        perfectScrollbar: 'js/perfectScrollbar/perfect-scrollbar.jquery.min',
        upload: 'bundles/taskstock/js/upload.min'
    },
    shim: {
        'tinymce': {
            exports: 'tinymce'
        }
    }
});

// configure already loaded dependencies
define('jquery', [], function() { return jQuery; });