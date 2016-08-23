var TaskCategoryCollection = Backbone.Collection.extend({
    model: TaskCategory,
    url: '/tasks/categories',
    parse: function(response) {
        return response.categories;
    }
});