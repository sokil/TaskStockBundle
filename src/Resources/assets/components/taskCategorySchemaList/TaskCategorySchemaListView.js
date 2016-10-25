var TaskCategorySchemaListView = Backbone.View.extend({
    events: {
        'click .edit': 'editButtonClickListener',
        'click .delete': 'deleteButtonClickListener'
    },

    initialize: function() {
        this.listenTo(this.collection, 'sync', this.renderAsync);
        this.collection.fetch();
    },

    reload: function() {
        this.collection.fetch();
    },

    renderAsync: function() {
        // show empty list
        if (this.collection.models.length === 0) {
            this.$el.html(app.render('TaskCategorySchemaEmptyList'));
            return;
        }

        // show list
        this.$el.html(app.render('TaskCategorySchemaList', {
            schemas: this.collection.models
        }));
    },

    editButtonClickListener: function(e) {
        var self = this;

        // get model
        var model = this.collection.add({id: $(e.currentTarget).data('id')});

        // render popup
        app.popup(new TaskCategorySchemaEditorPopupView({
            model: model,
            afterSave: function() {
                self.reload();
            }
        }));
    },

    deleteButtonClickListener: function(e) {
        var self = this,
            $btn = $(e.currentTarget);

        // get model
        var model = this.collection.add({id: $btn.data('id')});

        // delete
        model.on('sync', function(model, response) {
            if (response.error === 1) {
                alert(response.message);
                return;
            }
            $btn.closest('tr').remove();
        });
        model.destroy();
        
    }
});