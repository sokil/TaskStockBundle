var TaskProjectCollection = Backbone.Collection.extend({
    model: TaskProject,

    url: '/tasks/projects',

    projectsCount: null,

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
        this.projectsCount = response.projectsCount;
        return response.projects;
    }
});