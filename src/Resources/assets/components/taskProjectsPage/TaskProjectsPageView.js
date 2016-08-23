var TaskProjectsPageView = Marionette.LayoutView.extend({
    events: {
        'click #newTaskProject': 'newTaskProjectClickListener'
    },

    regions: {
        content: '.content'
    },

    listView: null,

    taskProjectCollection: null,

    initialize: function(options) {
        // create collection
        this.taskProjectCollection = new TaskProjectCollection();

        // render view
        this.listView = new TaskProjectListView({
            collection: this.taskProjectCollection
        });
    },

    render: function() {
        // render page
        this.$el.html(app.render('TaskProjectsPage'));
        // render list view
        this.content.show(this.listView);
    },

    newTaskProjectClickListener: function() {
        var self = this;

        // get model
        var model = this.taskProjectCollection.add({});

        // render popup
        app.popup(new TaskProjectEditorPopupView({
            model: model,
            afterSave: function() {
                self.content.currentView.render();
            }
        }));
    }
});