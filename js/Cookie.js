var Cookie = {

    setAndReload: function (data) {
        if (data.success) {
            Cookies.set("Hash", data.hash, {expires: 90});
            location.assign(data.url);
        }
    },

    removeHashAndReload: function () {
        Cookies.remove('Hash');
        location.assign(baseUrl);
    }

};
