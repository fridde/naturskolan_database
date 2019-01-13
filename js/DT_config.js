let DataTableConfigurator = {

    defaultOptions: {
        dom: 'fBt',
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
            buttons: {
                buttons: [
                    {
                        text: "Spara besöksordningen",
                        action: function (e, dt, node, config) {
                            e.data = ["tableReorder", ["School"]];
                            return Edit.change(e);
                        }
                    }
                ]
            }
        },
        Visit: {
            buttons: {buttons: [
                'colvis',
                    DataTableConfigurator.reusableButtons.hideOld,
                    DataTableConfigurator.reusableButtons.hideArchived
                ]}
        }

    },

    options: function (JQ) {
        let entity = JQ.closest("table[data-entity]").data("entity");
        if (typeof entity !== 'undefined' && typeof this.specialOptions[entity] !== 'undefined') {
            return $.extend({}, this.defaultOptions, this.specialOptions[entity]);
        } else {
            return this.defaultOptions;
        }
    },

    reusableButtons: {
        hideOld: {
            text: "Göm / visa tidigare",
            action: function (e, dt, node, config) {
                let $current_date = $('#today_date').data('date');
                dt.rows().nodes().to$().filter(function (i, el) {
                    return $(el).find('input[name="Date"]').val() >= $current_date;
                }).toggle();
            }
        },
        hideArchived: {
            text: 'Göm / visa arkiverade',
            action: function (e, dt, node, config) {
                dt.rows().nodes().to$().filter(function (i, el) {
                    return ! $(el).find('input[name="Status"]').val();
                }).toggle();
            }
        }

    }
};

