var TaskCategorySelectView = Backbone.View.extend({
    events: {
        'change input[type="checkbox"]': 'changeListener'
    },
    
    initialize: function(options) {
        app.container
            .get('taskCategories')
            .done($.proxy(this.renderCollection, this));

        if(options.change) {
            this.on('change', options.change, this);
        }
    },

    renderCollection: function(collection) {
        this.$el.html(app.render('TaskCategorySelect', {
            categories: collection.models
        }));
    },

    changeListener: function() {
        var categories = [];
        this.$el.find('input[type="checkbox"]:checked').each(function() {
            categories.push(this.value);
        });

        this.trigger('change', {
            categories: categories
        });
    }
});