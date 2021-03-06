'use strict';

const $ = require('jquery');
require('jqueryui');
const ButtonsAdmin = require('./ButtonsAdmin');
const Batch = require('./Batch');
const Edit = require('./Edit');


const DataTableConfigurator = require('./DT_config');

$(document).ready(() => {

    ButtonsAdmin.initialize();

    /**
     * Initialize sortable lists here
     */
    $('.sortable').sortable();

    $(Batch.listIdentifier).sortable(Batch.sortableOptions);

    $('.sortable.topic-visit-order').each(function(){
        $(this).on("sortstop", function(event){
            let segment = $(this).data('segment') ;
            event.data = ["tableReorder", ["Topic", segment]];
            Edit.change(event);
        });
    });

    $('.add-note-to-visit .editable').change('addNoteToVisit', Edit.change);

    $('.visits.set-colleagues td.toggle-label').click('work_schedule', Edit.change);

    $('.set-bookings td.toggle-label:not(.not-needed-td)').click('food_bus_bookings', Edit.change);
    $('.bus-settings td.toggle-label').click('bus_settings', Edit.change);

    DataTableConfigurator.create($('table.datatable'));


});
