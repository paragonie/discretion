/* Discretion */

window.zxcvbnResult = {"score": 0, "feedback": {"warning": "Not overwritten"}};
function check_password_strength() {
    window.zxcvbnResult = zxcvbn($("#passphrase").val(), [
        $("#username").val(),
        $("#emailAddress").val(),
        $("#realName").val()
    ]);
    var minimum = $("#registration-form").data("minpwstrength");
    if (!minimum) {
        minimum = 3;
    }
    return window.zxcvbnResult.score >= minimum;
}

$(document).ready(function() {
    $("#username").on('change', function() {
        var code = $("#qrcode-wrapper").data('placeholder').replace(/R_E_P_L_A_C_E_M_E/, $(this).val());
        $("#qrcode").html("").qrcode(code);
    });
    $("#passphrase-extra").html("").hide();
    $("#passphrase").on('change', function () {
        if (!check_password_strength()) {
            $("#passphrase-extra").html(window.zxcvbnResult["feedback"]["warning"]).show();
        } else {
            $("#passphrase-extra").html("").hide();
        }

        if ($("#passphrase2").val()) {
            if ($(this).val() !== $("#passphrase2").val()) {
                $("#passphrase2-extra").html("The provided passphrases do not match.").show();
            } else {
                $("#passphrase2-extra").html("").hide();
            }
        }
    });
    $("#passphrase2").on('change', function () {
        if ($(this).val() !== $("#passphrase").val()) {
            $("#passphrase2-extra").html("The provided passphrases do not match.").show();
        } else {
            $("#passphrase2-extra").html("").hide();
        }
    });
    $("#register-button").submit(function(e) {
        if (!check_password_strength()) {
            e.preventDefault();
            return false;
        }
    });
});
