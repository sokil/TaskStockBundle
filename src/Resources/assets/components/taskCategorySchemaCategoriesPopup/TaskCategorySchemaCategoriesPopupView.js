var TaskCategorySchemaCategoriesPopupView = PopupView.extend({

    events: {},

    title: 'Task Category Schema',

    buttons: [],

    init: function(params) {
        // create filtered collection
        var categoryCollection = new TaskCategoryCollection({
            filter: {
                schemaId: params.schemaId
            }
        });

        // sync filtered collection
        categoryCollection.fetch();

        // on collection sync - render
        this.listenTo(
            categoryCollection,
            'sync',
            function() {
                this.setBody(new MultiTypeaheadView({
                    typeahead: {
                        prefetch: {
                            url: '/tasks/categories',
                            transform: function (response) {
                                return response.categories;
                            }
                        }
                    },
                    list: {
                        collection: categoryCollection,
                        modelValue: function(model) {
                            return model.get('name');
                        },
                        modelId: function(model) {
                            return model.id;
                        }
                    }
                }));
            }
        );
    }
});