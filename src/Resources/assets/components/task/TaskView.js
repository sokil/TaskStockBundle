var TaskView = Marionette.LayoutView.extend({
    regions: {
        taskStateSwitcher: '#taskStateSwitcher',
        comments: '#comments',
        newCommentForm: '#newCommentForm',
        attachments: '#attachments',
        newAttachment: '#newAttachment',
    },

    events: {
        'click .deleteTask': 'deleteTaskClickEventListener',
        'click #newComment': 'newCommentClickEventListener',
        'click #newSubtask': 'newSubtaskClickEventListener',
        'click .editSubTask': 'editSubtaskClickEventListener',
        'click .deleteSubTask': 'deleteSubTaskClickEventListener',
        'click [data-comment-direction]': 'changeCommentDirectionButtonClickListener'
    },

    template: false,

    taskCommentCollection: null,

    taskAttachmentCollection: null,

    initialize: function() {
        this.listenTo(this.model, 'sync', this.renderAsync);
        this.model
            .fetch({
                data: {subtasks: true}
            })
            .fail(function() {
                app.router.navigate('tasks', {trigger: true});
            });

        // init comment collection
        this.taskCommentCollection = new TaskCommentCollection(null, {
            taskId: this.model.get('id')
        });

        // subscribe to change direction
        this.listenTo(this.taskCommentCollection, 'change:sortDirection', this.changeCommentDirectionListener);

        // init attachment collection
        this.taskAttachmentCollection = new TaskAttachmentCollection(null, {
            taskId: this.model.get('id')
        });
    },

    renderAsync: function() {
        var self = this;

        // render task page
        this.$el.html(app.render('Task', {
            task: this.model,
            commentDirection: this.taskCommentCollection.getSortDirection()
        }));

        // render task state switcher
        this.taskStateSwitcher.show(new ButtonGroupView({
            buttons: this.model.get('nextStates'),
            buttonClass: 'btn-success',
            click: function(transitionName) {
                var groupButtonView = this;
                $.post('/tasks/' + self.model.get('id') + '/state/' + transitionName)
                    .done(function(response) {
                        // re-render state button group
                        groupButtonView
                            .setButtons(response.nextStates)
                            .render();

                        // chenge current state label
                        self.$el.find('#taskStateLabel').text(response.state.label);
                    })
                    .fail(function(xhr) {});
            }
        }));

        // render comments
        this.comments.show(new TaskCommentsView({
            collection: this.taskCommentCollection
        }));

        // render attachments
        if (this.model.get('permissions').viewAttachments === true) {
            // show list
            this.attachments.show(new TaskAttachmentsView({
                collection: this.taskAttachmentCollection
            }));

            // init add button
            require(['upload'], function(upload) {
                // create progress
                var progressView = new ProgressView({
                    template: '<%= currentValue %> %',
                    el: self.$el.find('#progress')
                });
                progressView.$el.hide();
                progressView.render();

                // init upload handler
                $('#attachmentButton').upload({
                    uploadUrl: '/tasks/' + self.model.get('id') + '/attachments',
                    onbeforeupload: function() {
                        // show progress
                        progressView.$el.show();
                    },
                    onprogress: function(loaded, total) {
                        progressView.setCurrentValue(
                            Math.ceil(loaded / total) * 100
                        );
                    },
                    onsuccess: function(response) {
                        // add model to collection
                        self.taskAttachmentCollection.add(
                            new TaskAttachment(response.attachment)
                        );

                        // hide progress
                        setTimeout(function() {
                            progressView.$el.hide();
                            progressView.setCurrentValue(0);
                        }, 500);
                    }
                });
            });
        }
    },

    deleteTaskClickEventListener: function() {
        this.model
            .destroy()
            .done(function(response) {
                app.router.navigate('tasks', {trigger: true});
            });
    },

    newCommentClickEventListener: function(e) {
        var $container = this.$el.find('#newCommentForm'),
            $btn = $(e.currentTarget),
            self = this;

        // toggle form
        if (!$container.data('loaded')) {
            // render form
            var taskCommentFormView = new TaskCommentFormView({
                collection: this.taskCommentCollection,
            });
            this.newCommentForm.show(taskCommentFormView);
            $container.data('loaded', true);
        } else {
            $container.toggle();
        }

        // button
        if ($container.is(':visible')) {
            $btn
                .addClass('active')
                .find('.glyphicon')
                .removeClass('glyphicon-plus')
                .addClass('glyphicon-minus');
        } else {
            $btn
                .removeClass('active')
                .find('.glyphicon')
                .removeClass('glyphicon-minus active')
                .addClass('glyphicon-plus');
        }
    },

    newSubtaskClickEventListener: function() {
        // get model
        var taskCollection = new TaskCollection();
        var subtask = taskCollection.add({parent: this.model.get('id')});

        // init popup
        app.popup(new TaskEditorPopupView({
            model: subtask
        }));
    },

    editSubtaskClickEventListener: function(e) {
        // get model
        var modelId = $(e.currentTarget).data('id');
        var taskCollection = new TaskCollection();
        var subtask = taskCollection.add({id: modelId});

        // init popup
        app.popup(new TaskEditorPopupView({
            model: subtask
        }));
    },

    deleteSubTaskClickEventListener: function(e) {
        var $a = $(e.currentTarget);
        // get model
        var modelId = $a.data('id');
        var taskCollection = new TaskCollection();
        var subtask = taskCollection.add({id: modelId});

        // destroy model
        subtask
            .destroy()
            .done(function(response) {
                $a.closest('TR').remove();
            });
    },

    /**
     * Listener of button that changes comment's sort direction
     */
    changeCommentDirectionButtonClickListener: function() {
        this.taskCommentCollection.toggleSortDirection();
    },

    /**
     * Listen to changing of comment dierction in collection
     */
    changeCommentDirectionListener: function(e) {

        var $btn = this.$el.find('[data-comment-direction]');

        if (e.direction === 'desc') {
            $btn
                .removeClass('glyphicon-chevron-up')
                .addClass('glyphicon-chevron-down');
        } else {
            $btn
                .removeClass('glyphicon-chevron-down')
                .addClass('glyphicon-chevron-up');
        }

        $btn.attr('data-comment-direction', e.direction);
    }
});