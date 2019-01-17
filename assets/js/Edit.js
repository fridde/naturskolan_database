let Update = require('./Update');

class Edit {

    constructor(){
        this.recentChange = false;
        this.saveDelay = 0;
    }

    change(event) {

        let $this = event.this ? $(event.this) : $(this);

        let data = {};
        let option, specialInfo, $tr, $td, $tarea, $icon, list;
        if (this.recentChange !== false) {
            clearTimeout(this.recentChange);
        }
        if (event.data instanceof Object) {
            option = event.data[0];
            specialInfo = event.data[1];
        } else if (typeof event.data === 'string') {
            option = event.data;
        }

        switch (option) { // will contain the "option" added to the function

            case "group":
                data.updateMethod = "updateProperty";
                data.entity_class = "Group";
                data.entity_id = $this.closest(".group-container").data("entity-id");
                data.property = $this.prop("name").split('#').shift();
                data.value = $this.val();
                break;

            case "tableInput":

                data.entity_class = $this.closest("table").data("entity");
                data.entity_id = $this.closest("tr").data("id").toString();
                data.property = $this.prop("name").split('#').shift();
                if ($this.attr("type") === "radio") {
                    data.value = $this
                        .closest("tr").find("[name='" + $this.attr("name") + "']:checked")
                        .val();
                } else {
                    data.value = $this.val();
                }

                if (data.entity_id.charAt(0) === '#') {  // i.e. is a new object
                    data.entity_id = data.entity_id.substring(1);
                    data.return = {'old_id': data.entity_id};
                    data.updateMethod = 'createNewEntity';
                    //data.properties = {};

                    data.properties = $(".additional-information").data('default-properties') || {};
                    let props = JSON.parse($this.closest("tr").data('properties') || "{}");
                    Object.assign(data.properties, props);
                    data.properties[data.property] = data.value;

                } else {
                    data.updateMethod = "updateProperty";
                }
                // setting the new data-order and data-search for DataTables
                $this.data("search", data.value).data("order", data.value)
                    .attr("data-search", data.value).attr("data-order", data.value);
                break;

            case "groupModal":
                data.updateMethod = "updateGroupName";
                data.entity_class = "group";
                data.entity_id = $this.closest("#group-change-modal").data("entity-id");
                data.property = $this.prop("name").split('#').shift();
                data.value = $this.val();
                break;

            case "tableReorder":
                data.updateMethod = "updateVisitOrder";
                data.entity_class = specialInfo[0]; // the button should have a data-entity attribute
                if(data.entity_class === 'School') {
                    list = $('table[data-entity="School"] tbody');
                } else if(data.entity_class === 'Topic') {
                    list = $('ul.topic-visit-order[data-segment="'+ specialInfo[1] + '"]');
                }
                data.order = list.sortable("toArray", {attribute: "data-id"});
                data.onReturn = "reloadPage";
                break;

            case "visitConfirm":
                data.updateMethod = "updateProperty";
                data.entity_class = "Visit";
                data.entity_id = $this.data("visit-id");
                data.property = "Confirmed";
                data.value = true;
                data.onReturn = "changeConfirmedLink";
                break;

            case "work_schedule":
                $this.toggleClass('active');
                $tr = $this.closest('tr');
                data.updateMethod = "updateProperty";
                data.entity_class = "Visit";
                data.entity_id = $tr.data("id");
                data.property = "Colleagues";
                data.value = $.map($tr.find('td.active'), function(td){
                    return $(td).data('colleague-id');
                });
                data.value.push(null); // to ensure that it's not empty

                break;

            case 'food_bus_bookings':
                $this.toggleClass('active');
                $tr = $this.closest('tr');
                data.updateMethod = "updateProperty";
                data.entity_class = "Visit";
                data.entity_id = $tr.data("id");
                data.property = $this.data('booking-type') === 'food' ? 'FoodIsBooked' : 'BusIsBooked';
                data.value = $this.hasClass('active') ? 1 : 0;
                break;

            case 'bus_settings':
                $td = $this;
                $icon = $td.find('i');
                $tr = $icon.closest('tr');
                $icon.toggleClass('fa-minus').toggleClass('fa-bus');
                $td.toggleClass('active');

                data.updateMethod = "updateBusRule";
                data.school_id = $tr.data("school-id");
                data.location_id = $icon.closest('table')
                    .find('th').eq($td.index()).data('location-id');
                data.needs_bus = $td.hasClass('active') ? 1 : 0;
                break;

            case 'addNoteToVisit':
                $tarea = $this;

                data.updateMethod = 'updateNoteToVisit';
                data.author_id = $tarea.closest('.add-note-to-visit').find(':button.active').data('user-id');
                data.visit_id = $tarea.closest('.add-note-to-visit').data('visit-id');
                data.text = $tarea.val();

                $('.add-note-to-visit .prewritten-notes').data('notes')[data.author_id] = data.text;
                break;

            case 'timeproposal':
                data.updateMethod = 'updateProperty';
                data.entity_class = 'Visit';
                data.entity_id = $this.closest('li').data('visit-id');
                data.property = 'TimeProposal';
                data.value = $this.val();
                break;
        }

        if ($this.prop("type") === "checkbox") {
            data.property = $this.prop("name").split('#').shift();
            if (data.property.endsWith('[]')) {
                data.property = data.property.slice(0, -2);
            }
            let valueArray = [];
            let checkedBoxes = $this.closest("fieldset").find(":checked");
            checkedBoxes.each(function (index, element) {
                valueArray.push($(element).val());
            });
            data.value = valueArray.join();
        }

        if (["Food", "Mobil"].includes(data.property)) {
            Tooltip.check($this.get(0), data);
        }

        if(data.entity_class === 'User' && option === 'tableInput'){
            data.onReturn = 'groupUserOptions';
        }

        data.onReturn = data.onReturn || 'lastChange';
        this.recentChange = setTimeout(Update.send(data), this.saveDelay);
    }
}

module.exports = new Edit();
