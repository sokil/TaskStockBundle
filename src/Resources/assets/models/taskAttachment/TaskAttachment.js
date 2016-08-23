var TaskAttachment = Backbone.Model.extend({

    defaults: {
        path: null,
        name: null,
        size: null,
        createdAt: null
    },

    getCreatedAt: function() {
        return new Date(this.get('createdAt') * 1000);
    },

    getSize: function() {
        var filesize = this.get('size'),
            dimensions = ['B', 'KB', 'MB', 'GB'],
            dimension;

        for (var i in dimensions) {
            dimension = dimensions[i];
            if (filesize > 1024) {
                filesize = filesize / 1024;
                continue;
            }

            return (Math.round(filesize * 100) / 100) + ' ' + dimension;
        }

        return (Math.round(filesize * 100) / 100) + ' GB';
    }
});