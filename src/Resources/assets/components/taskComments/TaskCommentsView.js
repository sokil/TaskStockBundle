var TaskCommentsView = Backbone.View.extend({
    initialize: function() {
        this.listenToOnce(this.collection, 'sync', function() {
            this.renderListAsync();
            this.listenTo(this.collection, 'sort', this.renderListAsync);
        });
        this.collection.fetch();
    },

    renderListAsync: function() {
        if (this.collection.models.length === 0) {
            this.$el.html(app.render('TaskEmptyComments'));
            return;
        }

        require(
            [
                'taskstock/js/moment/moment.min',
                app.locale === 'en' ? null : 'taskstock/js/moment/' + app.locale
            ],
            function(moment) {
                this.$el.html(app.render('TaskComments', {
                    taskId: this.collection.taskId,
                    comments: _.map(this.collection.models, function(comment) {
                        return {
                            author: {
                                id: comment.get('author').id,
                                gravatar: comment.get('author').gravatar,
                                name: comment.get('author').name
                            },
                            date: {
                                text: moment(comment.getDate()).format('LLLL'),
                                fromNow: moment(comment.getDate()).fromNow()
                            },
                            text: comment.get('text')
                        };
                    })
                }));
            }.bind(this)
        );
    }
});