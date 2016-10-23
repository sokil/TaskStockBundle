var TaskProjectPermissionEditorPopupView = PopupView.extend({
    title: 'Task Permissions',

    buttons: [
        {class: 'btn-primary save', title: 'Save'}
    ],

    events: {
        'click .save': 'submitEventListener'
    },

    initialize: function() {
        if (!this.model.isNew()) {
            this.listenTo(this.model, 'sync', this.renderAsync);
            this.model.fetch();
        } else {
            this.renderAsync();
        }
    },

    renderAsync: function() {
        var self = this;
        app.container.get('taskProjectRoles').done(function(roles) {
            self.setBody(app.render('TaskProjectPermissionEditorPopup', {
                permission: self.model,
                roles: roles
            }));

            // init user typeahead
            require([
                'typeahead',
                'bloodhound'
            ], function() {
                self.$el.find('[data-user]')
                    .typeahead(null, {
                        name: 'user',
                        display: 'name',
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
                        templates: {
                            notFound: '<span class="empty">' + app.t('No users found') + '</span>',
                            suggestion: _.template('<div><img src="<%= gravatar %>?s=40&d=mm" class="img-circle" /> <%= name %></div>')
                        }
                    })
                    .bind('typeahead:selected', function (e, datum) {
                        self.$el.find('input[name="user"]').val(datum.id);
                    });

            });
        });

    },

    submitEventListener: function(e) {
        e.preventDefault();

        var self = this,
            $form = this.$el.find('form'),
            data = UrlMutator.unserializeQuery($form.serialize());

        this.model.save(data)
            .done(function() {
                self.remove();
            })
            .fail(function(xhr) {
                var response = xhr.responseJSON;
                if (response.validation) {
                    for (var fieldName in response.validation) {
                        $form
                            .find('[data-' + fieldName + ']')
                            .closest('.form-group')
                            .addClass('has-error')
                            .tooltip({
                                title: response.validation[fieldName],
                                placement: 'bottom',
                                trigger: 'manual'
                            })
                            .tooltip('show')
                            .on('change keydown', function() {
                                $(this)
                                    .tooltip('destroy')
                                    .removeClass('has-error');
                            });
                    }
                }

            });

        return false;
    }
});