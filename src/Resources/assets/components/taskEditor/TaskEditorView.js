var TaskEditorView = Marionette.ItemView.extend({
    tinymceEditor: null,

    template: false,

    events: {
        'click .save': 'saveEventHandler'
    },

    modal: false,

    initialize: function(options) {
        if (options.modal) {
            this.modal = options.modal;
        }

        // init model sync
        if (this.model.isNew()) {
            this.listenToOnce(this.model, 'syncDefaults', this.renderAsync);
            this.model.fetchDefaults();
        } else {
            this.listenToOnce(this.model, 'sync', this.renderAsync);
            this.model.fetch({
                data: {scenario: 'edit'}
            });
        }
    },

    onDestroy: function() {
        this.tinymceEditor.remove();
    },
    
    renderAsync: function() {
        var self = this;

        // render editor
        var formHTML = app.render('TaskEditorForm', {
            task: this.model,
            modal: this.modal
        });

        if (this.modal) {
            this.$el.html(formHTML);
        } else {
            this.$el.html(app.render('TaskEditorPage'));
            this.$el.find('.content').html(formHTML);
        }

        // init tinymce
        require(['tinymce'], function(tinymce) {
            // init
            tinymce.init({
                selector: '#taskEditorForm [name="description"]',
                plugins: "table link image code fullscreen textcolor",
                menubar: false,
                statusbar: false,
                toolbar: [
                    [
                        "undo redo",
                        "styleselect bold italic",
                        "forecolor backcolor",
                        "alignleft aligncenter alignright",
                        "link unlink",
                        "table",
                        "image",
                        "bullist numlist",
                        "outdent indent",
                        "code fullscreen"
                    ].join(" | ")
                ],
                setup: function(editor) {
                    self.tinymceEditor = editor;
                },
                resize: true
            });
        });

        app.loadCss([
            '/bundles/taskstock/css/typeahead.css'
        ]);

        require(['typeahead', 'bloodhound'], function() {
            if (self.model.hasPermission('changeAssignee')) {
                // assignee
                $('#txtAssignee')
                    .typeahead(null, {
                        name: 'assignee',
                        source: new Bloodhound({
                            queryTokenizer: Bloodhound.tokenizers.whitespace,
                            datumTokenizer: function (datum) {
                                return Bloodhound.tokenizers.whitespace(datum.name);
                            },
                            identify: function (datum) {
                                return datum.id;
                            },
                            remote: {
                                url: '/users?name=*',
                                wildcard: '*',
                                transform: function (response) {
                                    return response.users;
                                }
                            }
                        }),
                        display: function(datum) {
                            return datum.name;
                        },
                        templates: {
                            notFound: '<span class="empty">' + app.t('No users found') + '</span>',
                            suggestion: _.template('<div><img src="<%= gravatar %>?s=40&d=mm" class="img-circle" /> <%= name %></div>')
                        }
                    })
                    .bind('typeahead:selected', function (e, datum) {
                        self.$el.find('input[name="assignee"]').val(datum.id);
                    });
            }

            // owner
            if (self.model.hasPermission('changeOwner')) {
                $('#txtOwner')
                    .typeahead(null, {
                        name: 'owner',
                        source: new Bloodhound({
                            queryTokenizer: Bloodhound.tokenizers.whitespace,
                            datumTokenizer: function (datum) {
                                return Bloodhound.tokenizers.whitespace(datum.name);
                            },
                            identify: function (datum) {
                                return datum.id;
                            },
                            remote: {
                                url: '/users?name=*',
                                wildcard: '*',
                                transform: function (response) {
                                    return response.users;
                                }
                            }
                        }),
                        display: function(datum) {
                            return datum.name;
                        },
                        templates: {
                            notFound: '<span class="empty">' + app.t('No users found') + '</span>',
                            suggestion: _.template('<div><img src="<%= gravatar %>?s=40&d=mm" class="img-circle" /> <%= name %></div>')
                        }
                    })
                    .bind('typeahead:selected', function (e, datum) {
                        self.$el.find('input[name="owner"]').val(datum.id);
                    });

            }

            // project
            if (self.model.hasPermission('changeProject')) {
                $('#txtProject')
                    .typeahead(null, {
                        name: 'project',
                        display: 'name',
                        source: new Bloodhound({
                            queryTokenizer: Bloodhound.tokenizers.whitespace,
                            datumTokenizer: function (datum) {
                                return Bloodhound.tokenizers.whitespace(datum.name);
                            },
                            identify: function (datum) {
                                return datum.id;
                            },
                            prefetch: {
                                cache: false,
                                url: '/tasks/projects',
                                transform: function (response) {
                                    return response.projects;
                                }
                            }
                        }),
                        templates: {
                            notFound: '<span class="empty">' + app.t('No projects found') + '</span>',
                            suggestion: _.template('<div><%= name %></div>')
                        }
                    })
                    .bind('typeahead:selected', function (e, datum) {
                        self.$el.find('input[name="project"]').val(datum.id);
                    });
            }
        });
        
    },

    saveEventHandler: function(e) {
        var self = this;

        // prepare data
        var data = UrlMutator.unserializeQuery($('#taskEditorForm').serialize());
        data['description'] = tinymce.activeEditor.getContent();

        // show preloader
        this.$el.find('.status').addClass('spinner-small');

        // remove all previous validator messages
        this.$el.find('.form-group.has-error')
            .removeClass('has-error')
            .find('.help-block.error').remove();

        // save
        this.model
            .save(data)
            .always(function() {
                // hide preloader
                self.$el.find('.status').removeClass('spinner-small');
            })
            .done(function(response) {
                app.router.navigate('tasks/' + self.model.get('id'), {trigger: true});
            })
            .fail(function(xhr) {
                if (xhr.responseJSON.validation) {
                    for (var fieldName in xhr.responseJSON.validation) {
                        var $input = self.$el.find('[data-' + fieldName + ']');
                        if ($input.length === 0) {
                            $input = self.$el.find('INPUT[name="' + fieldName + '"]');
                        }
                        $input.closest('.form-group').addClass('has-error');
                        $input
                            .after($('<div class="help-block error">')
                            .text(xhr.responseJSON.validation[fieldName]));

                        // hide error on key press
                        $input.one('keypress', function() {
                            var $formGroup = $input.closest('.form-group');
                            $formGroup.removeClass('has-error');
                            $formGroup.find('.help-block.error').remove();
                        });
                    }
                };
            });

        return false;
    }
});