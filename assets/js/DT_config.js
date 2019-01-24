const $ = require('jquery');
require('jqueryui');

require('datatables.net');
require('datatables.net-buttons');
require('datatables.net-buttons/js/buttons.colVis');
require('datatables.net-bs4');
require('datatables.net-responsive-bs4');
require('datatables.net-fixedheader');
require('datatables.net-colreorder');
require('datatables.net-rowreorder');

require('../css/datatables.css');

const Edit = require('./Edit');

class DataTableConfigurator {

    constructor() {

        this.defaultOptions = {
            dom: 'fBt',
            paging:
                false,
            fixedHeader: {
                header: true
            },
            buttons: ['colvis']
        };

        this.specialOptions = {
            School: {
                buttons: this.defaultOptions.buttons.concat([
                    {
                        text: "Spara besöksordningen",
                        action: function (e, dt, node, config) {
                            e.data = ["tableReorder", ["School"]];
                            return Edit.change(e);
                        }
                    }
                ])
            },
            Visit: {
                buttons: this.defaultOptions.buttons.concat([
                    'colvis',
                    this.getReusableButton('hideOld'),
                    this.getReusableButton('hideArchived')
                ])
            }
        };


        this.hideArchived = {
            text: 'Göm / visa arkiverade',
            action: function (e, dt, node, config) {
                dt.rows().nodes().to$().filter(function (i, el) {
                    return !$(el).find('input[name="Status"]').val();
                }).toggle();
            }
        }
    }

    create(jqueryObj){
        jqueryObj.DataTable(this.options(jqueryObj));
    }

    options(JQ) {
        let entity = JQ.closest("table[data-entity]").data("entity");
        if (typeof entity !== 'undefined' && typeof this.specialOptions[entity] !== 'undefined') {
            let combined = $.extend(true, {}, this.defaultOptions, this.specialOptions[entity]);
            return combined;
        } else {
            return this.defaultOptions;
        }
    }

    getReusableButton(name) {
        let Buttons = {
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
                        return !$(el).find('input[name="Status"]').val();
                    }).toggle();
                }
            }
        };

        return Buttons[name];
    }
}

module.exports = new DataTableConfigurator();
