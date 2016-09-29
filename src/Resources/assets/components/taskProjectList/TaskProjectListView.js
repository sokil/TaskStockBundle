var TaskProjectListView = Marionette.LayoutView.extend({
    events: {
        'click .delete': 'deleteButtonClickListener'
    },

    regions: {
        paginator: '.pagination-wrap'
    },

    render: function() {
        this.listenTo(this.collection, 'sync', this.renderAsync);
        this.collection
            .setLimit(20)
            .setPage(1)
            .fetchPage();
    },

    renderAsync: function() {
        // if no projects configured
        if (this.collection.models.length === 0) {
            this.$el.html(app.render('TaskProjectEmptyList'));
            return;
        }

        this.$el.html(app.render('TaskProjectList', {
            projects: this.collection.models,
        }));

        // render paginatipon
        var self = this;
        this.paginator.show(
            new PaginationView({
                itemCount: this.collection.projectsCount,
                itemCountPerPage: this.collection.limit,
                currentPage: this.collection.page,
                change: function(e) {
                    self.collection.setPage(e.page).fetchPage();
                }
            }), {
                forceShow: true
            }
        );
    },

    deleteButtonClickListener: function(e) {
        var self = this,
            $btn = $(e.currentTarget);

        // get model
        var collection = new TaskProjectCollection();
        var model = collection.add({id: $btn.data('id')});

        // delete
        model.on('sync', function() {
            $btn.closest('tr').remove();
        });
        model.destroy();

    }
});