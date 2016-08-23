var TaskListView = Backbone.View.extend({
    
    initialize: function(options) {
        this.listenTo(this.collection, 'sync', this.renderAsync);
        this.collection
            .setLimit(20)
            .setPage(1)
            .fetchPage();
    },

    renderAsync: function() {
        var self = this;
        
        // render page
        this.$el.html(app.render('TaskList', {
            tasks: this.collection.models
        }));

        // render paginator
        if (this.collection.tasksCount) {
            var pagination = new PaginationView({
                el: this.$el.find('.pagination-wrap'),
                itemCount: this.collection.tasksCount,
                itemCountPerPage: this.collection.limit,
                currentPage: this.collection.page
            });

            pagination
                .on('change', function(e) {
                    self.collection
                        .setPage(e.page)
                        .fetchPage();
                })
                .render();
        }
    },

    setCategories: function(categories) {
        this.collection
            .setCategories(categories)
            .setPage(1)
            .fetchPage();
    
        return this;
    }
});