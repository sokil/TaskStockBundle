var TaskCategorySchemasPageView = Marionette.LayoutView.extend({
    events: {
        'click #newTaskCategorySchema': 'newTaskCategorySchemaClickListener'
    },

    regions: {
        content: '.content'
    },

    collection: null,

    initialize: function() {

        this.collection = new TaskCategorySchemaCollection();

        // render list
        this.listView = new TaskCategorySchemaListView({
            collection: this.collection
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
        var model = this.collection.add({});

        // render popup
        app.popup(new TaskCategorySchemaEditorPopupView({
            model: model,
            afterSave: function() {
                self.listView.render();
            }
        }));
    }
});