var TaskCategoryCollection = Backbone.Collection.extend({

    model: TaskCategory,

    url: '/tasks/categories',

    filter: null,

    initialize: function(params) {
        if (params.filter) {
            this.filter = params.filter;
        }
    },

    parse: function(response) {
        return response.categories;
    },

    fetch: function(options) {
        options = options || {};
        if (this.filter) {
            _.extend(
                options,
                {
                    data: {
                        filter: this.filter
                    }
                }
            );
        }

        return Backbone.Collection.prototype.fetch.call(this, options);
    }
});