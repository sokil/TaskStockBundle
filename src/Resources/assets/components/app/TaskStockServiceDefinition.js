var TaskStockServiceDefinition = {

    /**
     * Get list of task project roles
     */
    taskProjectRoles: function() {
        return $.get('/tasks/projects/roles');
    },

    notificationSchemasPromise: function() {
        return this.buildFetchablePromise(new NotificationSchemaCollection);
    }
};