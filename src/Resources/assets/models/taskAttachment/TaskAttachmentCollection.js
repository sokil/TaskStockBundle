var TaskAttachmentCollection = Backbone.Collection.extend({

    model: TaskAttachment,

    taskId: null,

    initialize: function(models, options) {
        if (options.taskId) {
            this.setTaskId(options.taskId);
        }
    },

    url: function() {
        return '/tasks/' + this.taskId + '/attachments';
    },

    setTaskId: function(taskId) {
        this.taskId = taskId;
        return this;
    },

    parse: function(response) {
        return response.attachments;
    }
});