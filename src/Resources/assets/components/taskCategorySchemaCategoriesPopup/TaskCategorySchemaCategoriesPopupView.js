var TaskCategorySchemaCategoriesPopupView = PopupView.extend({

    events: {},

    title: 'Task Category Schema',

    buttons: [],

    init: function(params) {
        if (!params.schemaId) {
            throw Error('Schema not specified');
        }

        // create filtered collection
        var categoryCollection = new TaskCategoryCollection({
            filter: {
                schemaId: params.schemaId
            }
        });

        var multiTypeaheadView = new MultiTypeaheadView({
            typeahead: {
                display: function(datum) {
                    return datum.name;
                },
                datumTokenizer: function(datum) {
                    return Bloodhound.tokenizers.whitespace(datum.name);
                },
                prefetch: {
                    url: '/tasks/categories',
                    transform: function (response) {
                        return response.categories;
                    }
                },
                templates: {
                    suggestion: _.template('<div><%= name %></div>'),
                },
                onSelect: function(model) {
                    $.post(
                        '/tasks/categorySchemas/' + params.schemaId + '/categories',
                        {
                            categories: [model.id]
                        }
                    );
                }
            },
            list: {
                collection: categoryCollection,
                columns: [
                    {
                        name: 'name',
                        caption: app.t('task_category_schema_categories.column.category_name')
                    }
                ],
                buttons: [
                    {
                        name: 'delete',
                        class: 'btn btn-danger btn-xs',
                        icon: 'glyphicon glyphicon-trash',
                        caption: app.t('task_category_schema_list.delete_btn'),
                        click: function(e, categoryId, listView) {
                            $.ajax({
                                url: '/tasks/categorySchemas/' + params.schemaId + '/categories/' + categoryId,
                                type: 'DELETE',
                                success: function() {
                                    // delete list item
                                    listView.remove(categoryId);
                                }
                            });
                        }
                    }
                ]
            }
        });

        // on collection sync - render
        this.listenTo(
            categoryCollection,
            'sync',
            function() {
                this.setBody(multiTypeaheadView);
            }
        );

        // sync filtered collection
        categoryCollection.fetch();
    }
});
