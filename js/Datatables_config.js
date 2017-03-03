var DataTableConfigurator = {

    defaultOptions: {
        dom: 'lft',
        paging: false,
        fixedHeader: {
            header: true
        }
    },

    specialOptions: {
        School: {
            dom: 'fBt',
            buttons: {
                buttons: [
                    {
                        text: "Spara besöksordningen",
                        action: function(e, dt, node, config){
                            var data = ["tableReorder", "School"];
                            e.data = data;
                            return Edit.change(e);
                        }
                    }
                ]
            }
        }

    },

    options: function(JQ){
        var entity = JQ.closest("table[data-entity]").data("entity");
        if(typeof entity !== 'undefined' && typeof this.specialOptions[entity] !== 'undefined'){
            return $.extend({}, this.defaultOptions, this.specialOptions[entity]);
        } else {
            return this.defaultOptions;
        }
    }

};
