let DataTableConfigurator = {

    defaultOptions: {
        dom: 'lfBt',
        paging: false,
        fixedHeader: {
            header: true
        },
        buttons: {
            buttons: [
                'colvis'
            ]
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
                            e.data = ["tableReorder", "School"];
                            return Edit.change(e);
                        }
                    }
                ]
            }
        }

    },

    options: function(JQ){
        let entity = JQ.closest("table[data-entity]").data("entity");
        if(typeof entity !== 'undefined' && typeof this.specialOptions[entity] !== 'undefined'){
            return $.extend({}, this.defaultOptions, this.specialOptions[entity]);
        } else {
            return this.defaultOptions;
        }
    }

};
