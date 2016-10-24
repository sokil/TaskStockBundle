var TaskCategorySchemaCollection = Backbone.Collection.extend({
    model: TaskCategory,
    url: '/tasks/categorySchemas',
    parse: function(response) {
        return response.categories;
    }
});