
(function () {
    $('h2').hide();
    $("#form_button").click(function () {
        $.ajax({
            url: "process.php",
            type: "POST",
            dataType: "json",
            data: {'value': $('#email-header-data').val()},
            success: function (data)
            {
                $('#dataTable tbody,#dataTable1 tbody,#dataTable2 tbody').html('');
                $('h2').hide();
                $.each(data, function (i, val) {

                    if (i !== 'details') {
                        val = val.replace("&", "&amp;");
                        val = val.replace("<", "&lt;");
                        val = val.replace(">", "&gt;");
                        $('#dataTable tbody').append('<tr><td class="key-section">' + i + '</td><td> ' + val + '</td></tr>')
                    } else {

                        $.each(val, function (j, k) {
                            $.each(k[0], function (m, n) {
                                $('#dataTable1 tbody').append('<tr><td class="key-section">' + m + '</td><td> ' + n + '</td></tr>')
                            });
                            $.each(k[1], function (m, n) {
                                $('#dataTable2 tbody').append('<tr><td class="key-section">' + m + '</td><td> ' + n + '</td></tr>')
                            });
                            $('#dataTable1 tbody').append('<tr class="empty"><td class=""></td><td> </td></tr>')
                            $('#dataTable2 tbody').append('<tr class="empty"><td class=""></td><td></td></tr>')
                        });
                    }

                });
                $('h2').show();
            },
            error: function (jqXHR, textStatus, errorThrown)
            {

            }
        });
    });
})();
