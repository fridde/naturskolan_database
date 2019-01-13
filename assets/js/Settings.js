class Settings {

    constructor() {
        this.datepickerOptions = {
            format: 'yyyy-mm-dd',
            weekStart: 1,
            language: 'sv',
            todayHighlight: true,
            calendarWeeks: true
        };
    }
}

module.exports = new Settings();
