var TaskProjectParametersView = Backbone.View.extend({
    
    events: {
        'submit form': 'submitListener'
    },

    initialize: function(params) {
        if (this.model.isNew() || this.model.get('code')) {
            this.renderAsync();
        } else {
            this.listenTo(this.model, 'sync', this.renderAsync);
            this.model.fetch();
        }
    },

    renderAsync: function() {
        var self = this;
        app.container.get('notificationSchemasPromise').done(function(notificationSchemaCollection) {
            self.$el.html(app.render('TaskProjectParameters', {
                project: self.model,
                notificationSchemas: notificationSchemaCollection.models
            }));
        });
    },

    submitListener: function(e) {
        e.preventDefault();
        var self = this,
            $form = this.$el.find('form');

        // remove status
        this.$el.find('.status').addClass('spinner-small');
        
        // save model
        this.model
            .save(UrlMutator.unserializeQuery($form.serialize()))
            .always(function() {
                self.$el.find('.status').removeClass('spinner-small');
            })
            .done(function(response) {

            });

        return false;
    }
});