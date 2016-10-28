var TaskCategoryCollection = Backbone.Collection.extend({

    model: TaskCategory,

    url: function() {
        var url = '/tasks/categories';

        // add filter
        if (this.filter) {
            url += '?' + $.param({filter: this.filter});
        }

        return url;
    },

    filter: null,

    parse: function(response) {
        return response.categories;
    },

    initialize: function(params) {
        this.filter = params.filter;
    }
});