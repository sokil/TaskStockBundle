var Task = Backbone.Model.extend({
    urlRoot: '/tasks',

    hasPermission: function(permission) {
        return this.get('permissions')[permission] === true;
    }
});