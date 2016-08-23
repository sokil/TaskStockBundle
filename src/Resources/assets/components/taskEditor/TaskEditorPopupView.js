var TaskEditorPopupView = PopupView.extend({
    title: 'Subtask',

    dialogClass: 'modal-lg',

    buttons: [
        {
            class: 'btn-primary save',
            title: 'Save'
        }
    ],

    events: {
        'click .save': function(e) {
            //save form
            this.view.saveEventHandler.call(this.view, e);
            // hide modal
            this.remove();
        }
    },

    init: function(options) {
        this.view = new TaskEditorView({
            model: this.model,
            modal: true
        });
    }
});