var TaskStockRouter = Marionette.AppRouter.extend({

    routes: {
        "tasks": "tasksAction",
        "tasks/new": "editTaskAction",
        "tasks/:id/edit": "editTaskAction",
        "tasks/:id": "taskAction",
        "taskCategories": "taskCategoriesAction",
        "taskCategorySchemas": "taskCategorySchemasAction",
        "projects": "taskProjectsAction",
        "projects/:id": "taskProjectAction",
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

    taskCategorySchemasAction: function() {
        app.rootView.content.show(new TaskCategorySchemasPageView());
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
    }
});