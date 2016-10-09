var TasksPageView = Marionette.LayoutView.extend({
    regions: {
        list: '#tasks-list',
        categorySelect: '#tasks-category-select'
    },
    
    render: function() {
        // render page
        this.$el.html(app.render('TasksPage'));

        // render list view
        var taskListView = new TaskListView({
            collection: new TaskCollection()
        });

        this.list.show(taskListView);
    }
});