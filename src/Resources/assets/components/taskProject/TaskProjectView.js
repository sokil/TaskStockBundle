var TaskProjectView = Marionette.LayoutView.extend({
    template: false,
    events: {
        'show.bs.tab [data-target="#parameters"]': 'parametersTabClickEventHandler',
        'show.bs.tab [data-target="#permissions"]': 'permissionsTabClickEventHandler',
        'click .addUser': 'addUserClickHandler',
    },
    regions: {
        parametersTabPane: '#parameters .wrapper',
        permissionsTabPane: '#permissions .wrapper'
    },
    initialize: function() {
        this.listenToOnce(this.model, 'sync', this.renderAsync);
        this.model.fetch();

        this.permissionCollection = new TaskProjectPermissionCollection(null, {
            projectId: this.model.get('id')
        });
    },

    renderAsync: function() {
        // render tabs
        this.$el.html(app.render('TaskProject', {
            project: this.model
        }));

        // show first tab
        this.$el.find('[data-target="#parameters"]').click();
    },

    parametersTabClickEventHandler: function(e) {
        var $tab = $(e.target);
        // load data when first click on tab
        if ($tab.data('loaded')) {
            return;
        }
        $tab.data('loaded', true);

        this.parametersTabPane.show(new TaskProjectParametersView({
            model: this.model
        }));
    },

    permissionsTabClickEventHandler: function(e) {
        var $tab = $(e.target);
        // load data when first click on tab
        if ($tab.data('loaded')) {
            return;
        }
        $tab.data('loaded', true);

        this.permissionsTabPane.show(new TaskProjectPermissionsView({
            collection: this.permissionCollection
        }));
    },

    addUserClickHandler: function() {

        var permission = this.permissionCollection.add({
            project: this.model.get('id')
        });

        app.popup(new TaskProjectPermissionEditorPopupView({
            model: permission
        }));
    }
});