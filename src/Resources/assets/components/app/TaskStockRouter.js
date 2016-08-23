var TaskStockRouter = Marionette.AppRouter.extend({

    routes: {
        "": "tasksAction",
        "tasks": "tasksAction",
        "tasks/new": "editTaskAction",
        "tasks/:id/edit": "editTaskAction",
        "tasks/:id": "taskAction",
        "taskCategories": "taskCategoriesAction",
        "projects": "taskProjectsAction",
        "projects/:id": "taskProjectAction",
        "users": "usersAction",
        "users/new": "editUserAction",
        "users/:id/edit": "editUserAction",
        "users/:id": "userAction",
        "roleGroups": "roleGroupsAction"
    },
    
    /**
     * Tasks list
     */
    tasksAction: function() {
        app.rootView.content.show(new TasksPageView());
    },

    /**
     * Task editor
     */
    editTaskAction: function(id) {
        // model
        var collection = new TaskCollection();
        var model = collection.add({});

        if (id && id !== 'new') {
            model.set('id', id);
        }

        app.rootView.content.show(new TaskEditorView({
            model: model
        }));
    },

    taskCategoriesAction: function() {
        app.rootView.content.show(new TaskCategoriesPageView());
    },

    taskProjectsAction: function() {
        app.rootView.content.show(new TaskProjectsPageView());
    },

    taskProjectAction: function(id) {
        // model
        var collection = new TaskProjectCollection();
        var model = collection.add({id: id});

        app.rootView.content.show(new TaskProjectView({
            model: model
        }));
    },

    taskAction: function(id) {
        // model
        var collection = new TaskCollection();
        var model = collection.add({id: id});

        // render page
        app.rootView.content.show(new TaskView({
            model: model
        }));
    },

    /**
     * Users list
     */
    usersAction: function() {
        app.rootView.content.show(new UsersPageView());
    },

    /**
     * User editor
     */
    editUserAction: function(id) {
        // model
        var collection = new UserCollection();
        var model = collection.add({});

        if (id && id !== 'new') {
            model.set('id', id);
        }

        app.rootView.content.show(new UserEditorView({
            model: model
        }));
    },

    /**
     * Show user
     */
    userAction: function(id) {
        // model
        var collection = new UserCollection();
        var model = collection.add({id: id});

        // render page
        app.rootView.content.show(new UserView({
            model: model
        }));
    },

    roleGroupsAction: function()
    {
        app.rootView.content.show(new RoleGroupsPageView());
    }
});