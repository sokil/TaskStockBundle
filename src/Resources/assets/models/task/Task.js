var Task = Backbone.Model.extend(_.extend(
    ModelFetchDefaultsTrait,
    {
        urlRoot: '/tasks',

        hasPermission: function(permission) {
            return this.get('permissions')[permission] === true;
        }
    }
));