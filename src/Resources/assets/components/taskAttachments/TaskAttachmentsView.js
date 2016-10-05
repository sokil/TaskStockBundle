var TaskAttachmentsView = Backbone.View.extend({

    events: {
        'click .delete': 'deleteEventHandler'
    },

    initialize: function() {
        this.listenTo(this.collection, 'sync change add', this.renderListAsync);
        this.collection.fetch();
    },

    renderListAsync: function() {
        if (this.collection.models.length === 0) {
            this.$el.html(app.render('TaskAttachmentsEmptyList'));
            return;
        }

        require(['taskstock/js/moment/moment.min'], function(moment) {
            this.$el.html(app.render('TaskAttachments', {
                taskId: this.collection.taskId,
                attachments: _.map(this.collection.models, function(attachment) {
                    return {
                        id: attachment.get('id'),
                        path: attachment.get('path'),
                        name: attachment.get('name'),
                        size: attachment.getSize(),
                        date: moment(attachment.getCreatedAt()).format('LLLL') + " (" + moment(attachment.getCreatedAt()).fromNow() + ")"
                    };
                })
            }));
        }.bind(this));
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