
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
                $.each(data, function (index, data) {
                    var i = data.key;
                    var val = data.value;
                    var status = false;

                    if (i !== 'details') {
                        val = val.replace("&", "&amp;");
                        val = val.replace("<", "&lt;");
                        val = val.replace(">", "&gt;");
                        $('#dataTable tbody').append('<tr><td class="key-section">' + i + '</td><td> ' + val + '</td></tr>')
                    } else {
                        if(!val){
                       $('#dataTable1 tbody').append('<tr class="empty"><td class="">There are no data available for analysis.</td></tr>')
                       $('#dataTable2 tbody').append('<tr class="empty"><td class="">There are no data available for analysis.</td></tr>')
                           
                    }else {
                        $.each(val, function (j, k) {
                          status = true;
                            $.each(k[0], function (m, n) {
                                $('#dataTable1 tbody').append('<tr><td class="key-section">' + m + '</td><td> ' + n + '</td></tr>')
                            });
                            $.each(k[1], function (m, n) {
                                if (m == 'msg') {

                                    $.each(n, function (o, p) {
                                        $('#dataTable2 tbody').append('<tr><td class="key-section ' + p.class + '">' + p.type + '</td><td class="' + p.class + '"> ' + p.msg + '</td></tr>')
                                    });
                                } else {
                                    $('#dataTable2 tbody').append('<tr><td class="key-section">' + m + '</td><td> ' + n + '</td></tr>')
                                }
                            });
                            $('#dataTable1 tbody').append('<tr class="empty"><td class=""></td><td> </td></tr>')
                            $('#dataTable2 tbody').append('<tr class="empty"><td class=""></td><td></td></tr>')
                        });
                    }
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
