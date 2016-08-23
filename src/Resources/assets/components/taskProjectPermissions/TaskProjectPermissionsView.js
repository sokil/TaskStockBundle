var TaskProjectPermissionsView = Backbone.View.extend({

    events: {
        'click .edit': 'editEventListener',
        'click .delete': 'deleteEventListener'
    },

    initialize: function() {
        this.listenTo(this.collection, 'sync', this.renderAsync);
        this.collection.fetch();
    },

    renderAsync: function() {
        this.$el.html(app.render('TaskProjectPermissions', {
            permissions: this.collection.models
        }));
    },

    editEventListener: function(e) {
        var permissionId = $(e.currentTarget).data('id');

        var permission = this.collection.get(permissionId);
        if (!permission) {
            permission = this.collection.add({});
        }

        app.popup(new TaskProjectPermissionEditorPopupView({
            model: permission
        }));
    },

    deleteEventListener: function(e) {
        var $btn = $(e.currentTarget);
        var permissionId = $btn.data('id');

        var permission = this.collection.get(permissionId);
        if (!permission) {
            permission = this.collection.add({});
        }

        // delete
        permission.on('sync', function() {
            $btn.closest('tr').remove();
        });
        permission.destroy();
    }
});