var TaskCategoriesPageView = Marionette.LayoutView.extend({
    events: {
        'click #newTaskCategory': 'newTaskCategoryClickListener'
    },

    regions: {
        content: '.content'
    },

    initialize: function() {
        // render list
        this.listView = new TaskCategoryListView({
            collection: new TaskCategoryCollection()
        });
    },

    render: function() {
        // render page
        this.$el.html(app.render('TaskCategoriesPage'));
        // render list view
        this.content.show(this.listView);
    },

    newTaskCategoryClickListener: function() {
        var self = this;

        // get model
        var collection = new TaskCategoryCollection();
        var model = collection.add({});

        // render popup
        app.popup(new TaskCategoryEditorPopupView({
            model: model,
            afterSave: function() {
                self.listView.render();
            }
        }));
    }
});