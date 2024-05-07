var Config = {
    url: $.parseJSON($.ajax({
        type: "GET",
        url: "http://app.newhumano2024.web/humano_config.json",
        dataType: "json",
        global: false,
        async: false,
        success: function (data) {
            return data;
        }
    }).responseText).url
}
