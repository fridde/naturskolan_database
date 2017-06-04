var Edit = {

    change: function (event) {
        var data = {};
        var option, specialInfo;
        if (recentChange !== false) {
            clearTimeout(recentChange);
        }
        if (typeof event.data === "object") {
            option = event.data[0];
            specialInfo = event.data[1];
        } else if (typeof event.data === "string") {
            option = event.data;
        }

        switch (option) { // will contain the "option" added to the function

            case "group":
                data.updateMethod = "updateProperty";
                data.entity_class = "Group";
                data.entity_id = $(this).closest(".group-container").data("entity-id");
                data.property = $(this).prop("name").split('#').shift();
                data.value = $(this).val();
                break;

            case "tableInput":

                data.entity_class = $(this).closest("table").data("entity");
                var entity_id = $(this).closest("tr").data("id").toString().split('#');
                if (entity_id[0] === 'new') {
                    data.updateMethod = 'createNewEntityFromModel';
                    data.model_entity_id = entity_id[1];
                } else {
                    data.updateMethod = "updateProperty";
                    data.entity_id = entity_id[0];
                }
                data.property = $(this).prop("name").split('#').shift();
                if ($(this).attr("type") === "radio") {
                    data.value = $(this)
                        .closest("tr").find("[name='" + $(this).attr("name") + "']:checked")
                        .val();
                } else if (specialInfo === "datepicker") {
                    if (event.dates.length > 1) {
                        data.value = event.dates;
                    } else {
                        data.value = event.format();
                    }
                } else {
                    data.value = $(this).val();
                }
                // setting the new data-order and data-search for DataTables
                $(this).data("search", data.value).data("order", data.value)
                    .attr("data-search", data.value).attr("data-order", data.value);
                break;

            case "groupModal":
                data.updateMethod = "updateGroupName";
                data.entity_class = "group";
                data.entity_id = $(this).closest("#group-change-modal").data("entity-id");
                data.property = $(this).prop("name").split('#').shift();
                data.value = $(this).val();
                break;

            case "tableReorder":
                data.updateMethod = "updateVisitOrder";
                data.entity_class = specialInfo; // the button should have a data-entity attribute
                data.property = "VisitOrder";
                data.order = $('table[data-entity="' + data.entity_class + '"] tbody')
                    .sortable("toArray", {attribute: "data-id"});
                data.onReturn = "reloadPage";
                break;

            case "visitConfirm":
                data.updateMethod = "updateProperty";
                data.entity_class = "Visit";
                data.entity_id = $(this).data("visit-id");
                data.property = "Confirmed";
                data.value = true;
                data.onReturn = "changeConfirmedLink";
                // TODO: Implement this in html/js
                break;


        }

        if ($(this).prop("type") == "checkbox") {
            data.property = $(this).prop("name");
            if (data.property.endsWith('[]')) {
                data.property = data.property.slice(0, -2);
            }
            var valueArray = [];
            var checkedBoxes = $(this).closest("fieldset").find(":checked");
            checkedBoxes.each(function (index, element) {
                valueArray.push($(element).val());
            });
            data.value = valueArray.join();
        }

        if (["Food", "Mobil"].indexOf(data.property) != -1) {
            Tooltip.check(this, data);
        }


        data.onReturn = data.onReturn || 'lastChange';
        recentChange = setTimeout(Update.send(data), saveDelay);
    }
};
