var TaskCategorySchemaCollection = Backbone.Collection.extend({
    model: TaskCategorySchema,
    url: '/tasks/categorySchemas',
    parse: function(response) {
        return response.schemas;
    }
});