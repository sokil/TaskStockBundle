var TaskProjectPermission = Backbone.Model.extend({
    user: null,
    url: function() {
        // stored model
        if (!this.isNew()) {
            return '/tasks/projects/permissions/' + this.get(this.idAttribute);
        }

        // new model
        if (this.collection) {
            return _.result(this.collection, 'url');
        } else {
            return '/tasks/projects/' + this.get('project') + '/permissions';
        }

    },
    initialize: function() {
        this.user = new User(this.get('user'));
        this.on('change:user', function() {
            this.user.set(this.get('user'));
        });
    }
});