let Calendar = {

    initialize: function () {
        $('#fullpage-calendar').fullCalendar({
            events: $('#calendar-events').data('events'),
            weekends: false,
            weekNumbers: true,
            locale: 'sv',
            editable: false,
            themeSystem: 'bootstrap4',
            defaultView: 'basicWeek',
            columnHeaderFormat: 'ddd D MMM'
            }
        );

    }
};
