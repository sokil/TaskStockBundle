var TaskCollection = Backbone.Collection.extend({
    model: Task,
    
    url: '/tasks',

    tasksCount: null,

    categories: null,

    limit: 20,

    page: 1,

    fetchPage: function() {
        return this.fetch({
            data: {
                // category
                category: this.categories,
                limit: this.limit,
                offset: (this.page - 1) * this.limit
            }
        });
    },
    
    setCategories: function(categories) {
        this.categories = categories;
        return this;
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
        this.tasksCount = response.tasksCount;        
        return response.tasks;
    }
});