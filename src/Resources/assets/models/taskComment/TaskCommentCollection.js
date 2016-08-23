var TaskCommentCollection = Backbone.Collection.extend({

    model: TaskComment,

    taskId: null,

    sortDirection: 'asc',

    comparator: function(comment1, comment2) {
        if (comment1.get('date') === comment2.get('date')) {
            return 0;
        }

        if (this.sortDirection === 'asc') {
            return comment1.get('date') < comment2.get('date') ? 1: -1;
        } else {
            return comment1.get('date') > comment2.get('date') ? 1: -1;
        }
    },

    initialize: function(models, options) {
        if (options.taskId) {
            this.setTaskId(options.taskId);
        }

        if (options.sortDirection) {
            this.sortDirection = options.sortDirection === 'asc' ? 'asc' : 'desc';
        }

        this.on('change:sortDirection', this.sort);
    },

    url: function() {
        return '/tasks/' + this.taskId + '/comments';
    },

    setTaskId: function(taskId) {
        this.taskId = taskId;
        return this;
    },

    setSortDirection: function(direction) {
        if (this.sortDirection === direction) {
            return this;
        }

        this.sortDirection = (direction === 'asc') ? 'asc' : 'desc';
        this.trigger('change:sortDirection', {direction: this.sortDirection});
        return this;
    },

    toggleSortDirection: function() {
        return this.setSortDirection(this.sortDirection === 'asc' ? 'desc' : 'asc');
    },

    getSortDirection: function() {
        return this.sortDirection;
    },

    parse: function(response) {
        return response.comments;
    }
});