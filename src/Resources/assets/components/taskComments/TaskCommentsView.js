var TaskCommentsView = Backbone.View.extend({
    initialize: function() {
        this.listenTo(this.collection, 'sort change add', this.renderListAsync);
        this.collection.fetch();
    },

    renderListAsync: function() {
        this.$el.html(app.render('TaskComments', {
            taskId: this.collection.taskId,
            comments: this.collection.models
        }));
    }
});