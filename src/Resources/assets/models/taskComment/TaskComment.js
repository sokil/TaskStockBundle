var TaskComment = Backbone.Model.extend({
    getDate: function() {
        return new Date(this.get('date') * 1000);
    }
});