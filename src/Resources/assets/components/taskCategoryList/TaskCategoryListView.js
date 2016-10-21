var TaskCategoryListView = Backbone.View.extend({
    events: {
        'click .edit': 'editButtonClickListener',
        'click .delete': 'deleteButtonClickListener'
    },

    render: function() {
        this.listenTo(this.collection, 'sync', this.renderAsync);
        this.collection.fetch();
    },

    renderAsync: function() {
        if (this.collection.models.length) {
            this.$el.html(app.render('TaskCategoryList', {
                categories: this.collection.models
            }));
        } else {
            this.$el.html(app.render('TaskCategoryEmptyList'));
        }
    },

    editButtonClickListener: function(e) {
        var self = this;

        // get model
        var collection = new TaskCategoryCollection();
        var model = collection.add({id: $(e.currentTarget).data('id')});

        // render popup
        app.popup(new TaskCategoryEditorPopupView({
            model: model,
            afterSave: function() {
                self.render();
            }
        }));
    },

    deleteButtonClickListener: function(e) {
        var self = this,
            $btn = $(e.currentTarget);

        // get model
        var collection = new TaskCategoryCollection();
        var model = collection.add({id: $btn.data('id')});

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