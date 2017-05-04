
(function () {
    $("#form_button").click(function () {
        $.ajax({
            url: "process.php",
            type: "POST",
            dataType: "json",
            data: {'value': $('#email-header-data').val()},
            success: function (data)
            {
                $('#dataTable tbody').html('');
                $.each(data, function (i, val) {
                    val = val.replace("&", "&amp;");
                    val = val.replace("<", "&lt;");
                    val = val.replace(">", "&gt;");
                    $('#dataTable tbody').append('<tr><td class="key-section">' + i + '</td><td> ' + val + '</td></tr>')
                    console.log(i, '--', val);
                });
            },
            error: function (jqXHR, textStatus, errorThrown)
            {

            }
        });
    });
})();
