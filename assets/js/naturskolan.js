'use strict';

const $ = require('jquery');
require('jqueryui');
//require('jquery-ui-dist');
const moment = require('moment');
require('bootstrap');
const Update = require('./Update');
const Batch = require('./Batch');
const Edit = require('./Edit');
const Buttons = require('./Buttons');
require( 'datatables.net');
const DataTableConfigurator = require('./DT_config');
const Slider = require('./Slider');
const Tooltip = require('./Tooltip');

let baseUrl;
let updateUrl = "update";
Edit.saveDelay = Slider.saveDelay = 3000; //milliseconds between ajax savings
moment.locale('sv');


$(document).ready(function () {
    baseUrl = $("base").attr("href");

    $.ajaxSetup({
        url: baseUrl + updateUrl,
        type: 'POST',
        dataType: 'json'
    });

    Buttons.initialize();

    /**
     * Defines the interval between the "Uppdaterades senast..." text
     */
    $('.save-time').css('visibility', 'hidden'); // initially blank
    setInterval(Update.updateSaveTimeText, 10 * 1000);

    $('#login-modal').modal({backdrop: "static"});
    $('#password-modal').modal('hide');
    $('#visit-confirmation-modal').modal({backdrop: "static"});

    /**
     * Initialize sortable lists here
     */
    $('.sortable').sortable();

    $(Batch.listIdentifier).sortable(Batch.sortableOptions);

    $('.sortable.topic-visit-order').each(function(){
        $(this).on("sortstop", function(event){
            let segment = $(this).data('segment') ;
            event.data = ["tableReorder", ["Topic", segment]];
            return Edit.change(event);
        });
    });

    /**
     * Configuration of callbacks for several elements
     */
    $('.group-container .editable').not('[name="TimeProposal"]').change('group', Edit.change);
    $('.group-container .editable[name="TimeProposal"]').change('timeproposal', Edit.change);
    $('table.editable :input').not('.datepicker').change("tableInput", Edit.change);
    $('.add-note-to-visit .editable').change('addNoteToVisit', Edit.change);


    $('.visits.set-colleagues td.toggle-label').click('work_schedule', Edit.change);

    $('.set-bookings td.toggle-label:not(.not-needed-td)').click('food_bus_bookings', Edit.change);
    $('.bus-settings td.toggle-label').click('bus_settings', Edit.change);

    $("#group-change-modal input").change("groupModal", Edit.change);

    /**
     * Initializing tooltips, sliders and datepickers
     */
    $(".group-container .input-slider").each((i, element) => {
        Slider.set($(element), "group");
    });
    $("table .input-slider").each((i, element) => {
        Slider.set($(element), "table");
    });

    $("textarea.has-tooltip, input.has-tooltip").tooltip({
        title :  () => Tooltip.getContent($(this).attr('name')),
        html: true,
        trigger: 'manual',
        container: 'body'
    });

    $("i.has-tooltip").tooltip({
        title : () => Tooltip.getContent($(this).attr('name')),
        html: true,
        container: 'body'
    });

    /*
    let datepicker = $.fn.datepicker.noConflict();
    $.fn.BSdatepicker = datepicker;
    $('.datepicker').BSdatepicker(Settings.datepickerOptions);
    $('input.datepicker').on('change', function(event){
        if($(this).val().length > 0){
            event.this = this;
            event.data = 'tableInput';
            Edit.change(event);
        }
    });
    */

    $("#group-change-modal").dialog({
        autoOpen: false,
        title: "Ändra gruppnamn"
    });

    if (typeof(DataTableConfigurator) !== "undefined") {
        let $table = $("table");
        $table.DataTable(DataTableConfigurator.options($table));
    }

    //Calendar.initialize();

});
