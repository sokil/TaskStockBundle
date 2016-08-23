var TaskAttachmentsView = Backbone.View.extend({

    events: {
        'click .delete': 'deleteEventHandler'
    },

    initialize: function() {
        this.listenTo(this.collection, 'change add', this.renderListAsync);
        this.collection.fetch();
    },

    renderListAsync: function() {
        this.$el.html(app.render('TaskAttachments', {
            taskId: this.collection.taskId,
            attachments: this.collection.models
        }));
    },

    deleteEventHandler: function(e) {
        var $container = $(e.target).closest('[data-id]');
        var attachmentId = $container.data('id');
        var attachment = this.collection.get(attachmentId);
        attachment
            .destroy()
            .done(function(response) {
                $container.remove();
            });
    }
});