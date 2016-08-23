var TaskProjectPermissionCollection = Backbone.Collection.extend({

    model: TaskProjectPermission,

    projectId: null,

    initialize: function(models, options) {
        if (options.projectId) {
            this.projectId = options.projectId;
        }
    },

    url: function() {
        return '/tasks/projects/' + this.projectId + '/permissions';
    },

    permissionsCount: null,

    limit: 20,

    page: 1,

    fetchPage: function() {
        return this.fetch({
            data: {
                limit: this.limit,
                offset: (this.page - 1) * this.limit
            }
        });
    },

    setLimit: function(limit) {
        this.limit = limit;
        return this;
    },

    setPage: function(page) {
        this.page = page;
        return this;
    },

    parse: function(response) {
        this.permissionsCount = response.permissionsCount;
        return response.permissions;
    }
});