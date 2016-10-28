var TaskCategorySchemaCategoriesPopupView = PopupView.extend({

    events: {
        'click .save': 'saveButtonClickListener'
    },

    title: 'Task Category Schema',

    buttons: [
        {class: 'btn-primary save', title: 'Save'}
    ],

    init: function(params) {
        this.setBody(new MultiTypeaheadView({
            typeahead: {
                prefetch: {
                    url: '/tasks/categories',
                    transform: function (response) {
                        return response.categories;
                    }
                }
            },
            list: {
                collection: new Backbone.Collection([{name: 'hello1', id: 1}, {name: 'hello2', 'id': 2}]),
                modelValue: function(model) {
                    return model.get('name');
                },
                modelId: function(model) {
                    return model.id;
                }
            }
        }));
    },

    saveButtonClickListener: function() {

    }
});