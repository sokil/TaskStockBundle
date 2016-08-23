var TaskCommentFormView = Backbone.View.extend({
    events: {
        'submit form': 'submitFormEventListener'
    },

    initialize: function(options) {
        this.collection = options.collection;
    },

    render: function() {
        this.$el.html(app.render('TaskCommentForm', {

        }));
    },

    submitFormEventListener: function(e) {
        var data = UrlMutator.unserializeQuery(this.$el.find('form').serialize());

        // show preloader
        this.$el.find('.status').addClass('spinner-small');

        // new model instance
        var model = this.collection.add({}, {silent: true, sort: false});

        // save
        var self = this;
        model
            .save(data, {wait: true})
            .always(function() {
                // hide preloader
                self.$el.find('.status').removeClass('spinner-small');
            })
            .done(function(response) {
                self.$el.find('form')[0].reset();
                self.collection.sort();
                self.trigger('save', model);
            });

        return false;
    }
});