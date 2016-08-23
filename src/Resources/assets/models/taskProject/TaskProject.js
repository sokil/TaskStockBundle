var TaskProject = Backbone.Model.extend({
    stateSchemas: null,
    hasPermission: function(permission) {
        return this.get('permissions')[permission] === true;
    },
    parse: function(response) {
        this.stateSchemas = response.stateSchemas;
        delete response.stateSchemas;
        return response;
    }
});