var Cookie = {

	set : function(name, value, exdays) {
		var d = new Date();
		d.setTime(d.getTime() + (exdays*24*60*60*1000));
		var expires = "expires="+ d.toUTCString();
		document.cookie = name + "=" + value + "; " + expires;
	},

	setAndReload : function(data, status){
		if(data.success){
			Cookie.set("Hash", data.hash, 90);
			location.assign(baseUrl + "skola/" + data.school);
		}
	},

	get : function(name) {
		name += "=";
		var ca = document.cookie.split(';');
		for(var i = 0; i <ca.length; i++) {
			var c = ca[i];
			while (c.charAt(0)==' ') {
				c = c.substring(1);
			}
			if (c.indexOf(name) === 0) {
				return c.substring(name.length,c.length);
			}
		}
		return "";
	}
};
