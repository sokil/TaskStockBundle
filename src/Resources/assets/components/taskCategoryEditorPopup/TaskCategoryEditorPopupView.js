var TaskCategoryEditorPopupView = PopupView.extend({
    
    events: {
        'click .save': 'saveButtonClickListener'
    },

    title: 'Task Category',
    
    buttons: [
        {class: 'btn-primary save', title: 'Save'}
    ],

    init: function(params) {
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
        return app.render('TaskCategoryEditorPopup', {
            category: this.model
        });
    },

    saveButtonClickListener: function() {
        var self = this,
            $form = this.$el.find('form');

        // remove status
        this.$el.find('.status').removeClass('alert-warning').empty();
        
        // save model
        var xhr = this.model.save(UrlMutator.unserializeQuery($form.serialize()));
        xhr.success(function(response) {
            if (response.error === 0) {
                // trigger save event
                self.trigger('after:save');
                // close modal
                self.remove();
                return;
            }

            self.$el.find('.status').addClass('alert-warning').text(response.message);

            // show validation error
            if (response.validation) {
                for (var formName in response.validation) {
                    var $input;
                    for (var fieldName in response.validation[formName]) {
                        $input = $form
                            .find('[name="taskCategory[' + fieldName + ']"]')
                            .addClass('has-error')
                            .after($('<div class="help-block error">').text(response.validation[fieldName]))
                            .keydown(function() {
                                $(this).parent().find('.help-block.error').remove();
                            });
                    }
                }
            }
        });        
    }
});