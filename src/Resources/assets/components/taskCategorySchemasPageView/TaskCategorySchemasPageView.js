var TaskCategorySchemasPageView = Marionette.LayoutView.extend({
    events: {
        'click #newTaskCategorySchema': 'newTaskCategorySchemaClickListener'
    },

    regions: {
        content: '.content'
    },

    initialize: function() {
        // render list
        this.listView = new TaskCategorySchemaListView({
            collection: new TaskCategorySchemaCollection()
        });
    },

    render: function() {
        // render page
        this.$el.html(app.render('TaskCategorySchemasPage'));
        // render list view
        this.content.show(this.listView);
    },

    newTaskCategorySchemaClickListener: function() {
        var self = this;

        // get model
        var collection = new TaskCategorySchemaCollection();
        var model = collection.add({});

        // render popup
        app.popup(new TaskCategorySchemaEditorPopupView({
            model: model,
            afterSave: function() {
                self.listView.render();
            }
        }));
    }
});