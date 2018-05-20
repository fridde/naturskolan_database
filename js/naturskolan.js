let baseUrl;
let updateUrl = "update";
let recentChange = false;
let saveDelay = 3000; //milliseconds between ajax savings
moment.locale('sv');

$(document).ready(function () {

    baseUrl = $("base").attr("href");
    $.ajaxSetup({
        url: baseUrl + updateUrl,
        type: 'POST',
        dataType: 'json'
    });

    $('[data-toggle="tooltip"]').tooltip();


    /**
     * Defines the interval between the "Uppdaterades senast..." text
     */
    $('.save-time').css('visibility', 'hidden'); // initially blank
    setInterval(Update.updateSaveTimeText, 10 * 1000);

    $('#login-modal').modal({backdrop: "static"});
    $('#password-modal').modal('hide');
    $('#visit-confirmation-modal').modal({backdrop: "static"});


    $('.nav a[href="logout"]').click(function (e) {
        e.preventDefault();
        let data = {
            updateMethod: "removeCookie",
            hash: Cookies.get('Hash'),
            onReturn: 'removeCookie'
        };
        Update.send(data);
    });

    /**
     * Initialize sortable lists here
     */
    $('.sortable').sortable();

    $(Batch.listIdentifier).sortable(Batch.sortableOptions);

    /**
     * Configuration of callbacks for several elements
     */
    $editable = $(".editable");
    $('.group-container .editable').change('group', Edit.change);
    //$('table.editable :input').not(".datepicker").change("tableInput", Edit.change);
    $('table.editable :input').change("tableInput", Edit.change);

    $('.visits.set-colleagues td.toggle-label').click('work_schedule', Edit.change);

    $('.set-bookings td.toggle-label:not(.not-needed-td)').click('food_bus_bookings', Edit.change)


    $("#group-change-modal input").change("groupModal", Edit.change);

    /**
     * Initializing tooltips, sliders and datepickers
     */
    $(".group-container .input-slider").each(function (i, element) {
        Slider.set($(this), "group");
    });
    $("table .input-slider").each(function (i, element) {
        Slider.set($(this), "table");
    });

    $(".has-tooltip").tooltip({
            title : function () {
            return Tooltip.getContent($(this).attr('name'));
        },
        html: true,
        trigger: 'manual',
        container: 'body'
    });


    $('.datepicker').datepicker(Settings.datepickerOptions);


    $("#group-change-modal").dialog({
        autoOpen: false,
        title: "Ändra gruppnamn"
    });

    if (typeof(DataTableConfigurator) !== "undefined") {
        let $table = $("table");
        $table.DataTable(DataTableConfigurator.options($table));
    }


});
