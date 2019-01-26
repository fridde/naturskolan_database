'use strict';

const $ = require('jquery');
require('jqueryui');
const moment = require('moment/moment.js');
require('moment/locale/sv.js');
require('bootstrap');

const Update = require('./Update');
const Edit = require('./Edit');
const Slider = require('./Slider');
const Tooltip = require('./Tooltip');
const ButtonsBase = require('./ButtonsBase');

require('jquery-ui/themes/base/all.css');
require('bootstrap/dist/css/bootstrap.css');
require('../css/base.css');


let baseUrl;
let updateUrl = "update";
window.saveDelay = 3000;
window.recentChange = false;
Edit.saveDelay = Slider.saveDelay = 3000; //milliseconds between ajax savings
moment.locale('sv');

$(document).ready(() => {

    baseUrl = $("base").attr("href");

    $.ajaxSetup({
        url: baseUrl + updateUrl,
        type: 'POST',
        dataType: 'json'
    });

    ButtonsBase.initialize();


    $('.save-time').css('visibility', 'hidden'); // initially blank
    setInterval(Update.updateSaveTimeText, 10 * 1000);

    $('#login-modal').modal({backdrop: "static"});
    $('#password-modal').modal('hide');
    $('#visit-confirmation-modal').modal({backdrop: "static"});

    let $editableGroupContainer = $('.group-container .editable');
    let $timeProp = '[name="TimeProposal"]';
    $editableGroupContainer.not($timeProp).change('group', Edit.change);
    $editableGroupContainer.filter($timeProp).change('timeproposal', Edit.change);

    let $editableTableInput = $('table.editable :input');
    let $dp = '.datepicker';
    $editableTableInput.not($dp).change("tableInput", Edit.change);
    $editableTableInput.filter($dp).change("datepicker", Edit.change);

    $("#group-change-modal input").change("groupModal", Edit.change);

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

    $("#group-change-modal").dialog({
        autoOpen: false,
        title: "Ändra gruppnamn"
    });

});
