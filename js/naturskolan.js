var updateUrl = "update.php";

$(document).ready(function(){
	
	$('.modal').modal('show');
	$('#login_modal_submit').click(function(){
		updateValues(".modal input", "password", passwordCorrect);
	});
});

var updateValues = function(fieldSelector, updateType, successFunction){
	var response = {status: "error", data : {}, ajaxStatus: "error"};
	var data = "updateType=" + updateType + "&";
	data += $(fieldSelector).serialize();
	$.post(updateUrl, data, successFunction, "json");	
};

var passwordCorrect = function(data, status){
	if(status == "success" && data.status == "success"){
		$('.modal').modal('hide');
		$.post(updateUrl, "updateType=setCookie&school=" + data.school, setCookieAndReload, "json");
	}
};

var setCookieAndReload = function(data, status){
	if(status == "success"){
		setCookie("Hash", data.hash, 90);
		location.reload();
	}
	
}

var setCookie = function(name, value, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires="+ d.toUTCString();
    document.cookie = name + "=" + value + "; " + expires;
}

var getCookie = function(name) {
    name += "=";
    var ca = document.cookie.split(';');
    for(var i = 0; i <ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length,c.length);
        }
    }
    return "";
}