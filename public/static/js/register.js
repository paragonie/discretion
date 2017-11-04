$(document).ready(function() {
    $("#username").on('change', function() {
        var code = $("#qrcode-wrapper").data('placeholder').replace(/R_E_P_L_A_C_E_M_E/, $(this).val());
        $("#qrcode").html("").qrcode(code);
    });
});