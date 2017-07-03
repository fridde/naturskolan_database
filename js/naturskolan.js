var baseUrl;
var updateUrl = "update";
var recentChange = false;
var saveDelay = 3000; //milliseconds between ajax savings
moment.locale('sv');

$(document).ready(function(){

	baseUrl = $("base").attr("href");
	$.ajaxSetup({
		url: baseUrl + updateUrl,
		type: 'POST',
		dataType: 'json'
	});

	/**
	* Defines the interval between the "Uppdaterades senast..." text
	*/
	$('.save-time').css('visibility', 'hidden'); // initially blank
	setInterval(Update.setSaveTimeText, 10*1000);

	$('.modal').modal('show');
	var $login_modal_submit = $('#login_modal_submit');
    $login_modal_submit.click(function(){
		var data = {
			updateMethod: "checkPassword",
			password: $("[name='password']").val(),
			onReturn: 'passwordCorrect'
		};
		Update.send(data);
	});
	var predefined_pw = $('#login-modal input[name="password"]').val();
	if(typeof predefined_pw === "string" && predefined_pw !== ''){
        $login_modal_submit[0].click();
	}

	$('.nav a[href="logout"]').click(function(e) {
        e.preventDefault();
        var data = {
            updateMethod: "removeCookie",
            hash: Cookies.get('Hash'),
            onReturn: 'removeCookie'
        };
        Update.send(data);
    });

    /**
	* Initialize sortable lists here
	*/
	$(".sortable").sortable();

	$(Batch.listIdentifier).sortable(Batch.sortableOptions);

	/**
	* Configuration of callbacks for several elements
	*/
	$editable = $(".editable");
    $('.group-container .editable').change('group', Edit.change);
    $('table.editable :input').not(".datepicker").change("tableInput", Edit.change);
    $editable.filter(".datepicker").on("changeDate", ["tableInput", "datepicker"] , Edit.change);
	$("#group-change-modal input").change("groupModal", Edit.change);

	/**
	* Initializing tooltips, sliders and datepickers
	*/
	$(".group-container .input-slider").each(function(i,element){
		Slider.set($(this), "group");
	});
	$("table .input-slider").each(function(i,element){
		Slider.set($(this), "table");
	});

	$('[name="Food"]').tooltip({
		title: Tooltip.foodText,
		trigger: 'manual',
		html: true
	});
	$('[name="Mobil"]').tooltip({
		title: Tooltip.mobilText,
		trigger: 'manual',
		html: true
	});

	$(".datepicker").datepicker({
		format: "yyyy-mm-dd",
		weekStart: 1,
		calendarWeeks: true,
		language: 'sv'
	});



	$("#group-change-modal").dialog({
		autoOpen: false,
		title: "Ändra gruppnamn"
	});

	if(typeof(DataTableConfigurator) !== "undefined"){
	    var $table = $("table");
        $table.DataTable(DataTableConfigurator.options($table));
	}


});
