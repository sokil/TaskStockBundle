var TaskProjectEditorPopupView = PopupView.extend({
    
    events: {
        'click .save': 'saveButtonClickListener'
    },

    title: 'Task Project',
    
    buttons: [
        {class: 'btn-primary save', title: 'Save'}
    ],

    initialize: function(params) {
        var self = this;

        if (params.afterSave) {
            this.on('after:save', params.afterSave);
        }

        if (self.model.isNew()) {
            self.setBody(this.getEditorHtml());
        } else {
            self.model.on('sync', function() {
                self.setBody(self.getEditorHtml());
            });
            self.model.fetch();
        }
    },

    getEditorHtml: function() {
        return app.render('TaskProjectEditorPopup', {
            project: this.model
        });
    },

    saveButtonClickListener: function() {
        var self = this,
            $form = this.$el.find('form');

        // remove status
        this.$el.find('.status').removeClass('alert-warning').empty();

        // remove all previous validator messages
        this.$el.find('.form-group.has-error')
            .removeClass('has-error')
            .find('.help-block.error').remove();

        // save model
        var xhr = this.model.save(UrlMutator.unserializeQuery($form.serialize()));
        xhr
            .always(function() {
            })
            .done(function(response) {
                // trigger save event
                self.trigger('after:save');
                // close modal
                self.remove();
            })
            .fail(function() {
                if (xhr.responseJSON.validation) {
                    for (var fieldName in xhr.responseJSON.validation) {
                        self.$el.find('INPUT[name="' + fieldName + '"]')
                            .one('keypress', function(e) {
                                var $formGroup = $(e.currentTarget).closest('.form-group');
                                $formGroup.removeClass('has-error');
                                $formGroup.find('.help-block.error').remove();
                            })
                            .after(
                                $('<div class="help-block error">').text(xhr.responseJSON.validation[fieldName])
                            )
                            .closest('.form-group').addClass('has-error');
                    }
                };
            });
    }
});