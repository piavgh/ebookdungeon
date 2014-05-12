var Upload = {
    add_file: function(event)
    {
        var numFiles = event.files.length;
        this.toggle_btn(numFiles);
    },
    toggle_btn: function(numFiles)
    {
        // Toggle upload & cancel button
        if (numFiles > 0)
        {
            // Show btn
            $("#upall-btn").show();
            $("#cancelall-btn").show();
            $("#convert-checkbox").show();
        }
        else
        {
            // Hide btn
            $("#upall-btn").hide();
            $("#cancelall-btn").hide();
            $("#convert-checkbox").hide();
            $("#upsel-btn").hide();
            $("#delsel-btn").hide();
        }
    },
    getTotalSize: function(is_check)
    {
        var input = (is_check) ? $("input[name='file_act[]']:checked") : $("input[name='file_act[]']") ;
        var templates = input.closest('.template-upload');
        var total = 0;

        for (var i = 0; i < templates.length; i++) {
            var file = $(templates[i]).data('data').files[0];
            total += file.size;
        }
        return total;
    },
    showQuotaWarning: function(total_size)
    {
        BootstrapDialog.show({
            type: BootstrapDialog.TYPE_DANGER,
            title: 'Out of available space',
            message: 'Your uploading files (' + Util.formatFileSize(total_size) + ') exceed ' + Util.formatFileSize(Global.availableSpace) + ' available space',
            buttons: [{
                    label: 'Cancel',
                    action: function(dialogItself) {
                        dialogItself.close();
                    }
                }]
        });
    },
    uploadAll: function()
    {
        // Checking uploading quota
        var total_size = this.getTotalSize(false);
        if (total_size > Global.availableSpace) {
            this.showQuotaWarning(total_size);
            return false;
        }
        Global.triggerStartAll();
        return true;
    }
}

var LogIn = {
    check: function(id) {
        if ($.trim($("#" + id)[0].value) === '') {
            $("#" + id)[0].focus();
            $("#sign-in-warning").show();
            return false;
        }
        return true;
    },
    validate: function() {
        if (!LogIn.check("sign-in-username"))
            return false;
        if (!LogIn.check("sign-in-password"))
            return false;

        $("#sign-in-warning").hide();
        return true;
    }

};

var SignUp = {
    check: function(id) {
        if ($.trim($("#" + id)[0].value) === '') {
            $("#" + id)[0].focus();
            $("#" + id + "_alert").show();

            return false;
        }
        return true;
    },
    validate_userName: function() {
        if (SignUp.check("username") === false) {
            return false;
        }

        var id = "username";
        var warning = document.getElementById(id + '_alert');
        var username = $.trim($("#" + id)[0].value);
        // Length must be between 6 and 20
        var len = username.length;
        if (len < 6 || len > 20) {
            warning.innerHTML = "<strong class='warning'>Warning!</strong> User name's length must be between 6 and 20";
            $("#" + id)[0].focus();
            $(warning).show();
            return false;

        }
        // Contain only alpha-numeric character
        var regExp = /^[a-z0-9]+$/i;
        if (!username.match(regExp))
        {
            warning.innerHTML = "<strong class='warning'>Warning!</strong>  User name contains alphanumeric charactes only.";
            $("#" + id)[0].focus();
            $(warning).show();
            return false;
        }

        $(warning).hide();
        return true;
    },
    validate_email: function() {
        if (SignUp.check("email") === false) {
            return false;
        }

        // Validate email format
        var id = "email";
        var email = $.trim($("#" + id)[0].value);
        var warning = document.getElementById(id + '_alert');
        var atpos = email.indexOf("@");
        var dotpos = email.lastIndexOf(".");
        if (atpos < 1 || dotpos < atpos + 2 || dotpos + 2 >= email.length)
        {
            warning.innerHTML = "<strong class='warning'>Warning!</strong> Invalid email.";
            $("#" + id)[0].focus();
            $(warning).show();
            return false;
        }
        $(warning).hide();
        return true;
    },
    validate_phone: function() {
        if (SignUp.check("phone") === false) {
            return false;
        }
        // Phone contains only numbers
        var id = "phone";
        var warning = document.getElementById(id + '_alert');
        var phone = $.trim($("#" + id)[0].value);
        var regExp = /^[0-9]+$/;
        if (!phone.match(regExp))
        {
            warning.innerHTML = "<strong class='warning'>Warning!</strong>  Phone number contains numbers only.";
            $("#" + id)[0].focus();
            $(warning).show();
            return false;
        }

        $(warning).hide();
        return true;
    },
    validate_password: function() {
        if (SignUp.check("password") === false) {
            return false;
        }

        var id = "password";
        var warning = document.getElementById(id + '_alert');
        var password = $.trim($("#" + id)[0].value);
        // Length must be between 6 and 20
        var len = password.length;
        if (len < 6 || len > 20) {
            warning.innerHTML = "<strong class='warning'>Warning!</strong> Password length must be between 6 and 20";
            $("#" + id)[0].focus();
            $(warning).show();
            return false;

        }

        $(warning).hide();
        return true;
    },
    validate: function() {
        if (!SignUp.validate_userName())
            return false;
        if (!SignUp.validate_email())
            return false;
        if (!SignUp.validate_phone())
            return false;
        if (!SignUp.validate_password())
            return false;
        if ($("#password")[0].value != $("#repeat_password")[0].value) {
            $("#repeat_password")[0].focus();
            $("#repeat_password_alert").show();

            return false;
        }
        return true;
    }

};

var Util = {
    toggle_group_field: function(event)
    {
        var value = event.options[event.selectedIndex].value;
        var group = $("#control-groupname");
        if (value == "individual")
        {
            // Hide group field
            $("#groupname").removeAttr('required');
            group.hide("slow");
        } else if (value == "group")
        {
            // Show group 
            $("#groupname").prop('required', true);
            group.show("slow");
        }
    },
    toggle_checkbox: function(event, name, callback)
    {
        var checkValue = event.checked;
        var checkboxes = document.getElementsByName(name);
        var numCheck = checkboxes.length;

        for (var i = 0; i < numCheck; i++)
        {
            checkboxes[i].checked = checkValue;
        }

        // Execute the callback function
        if ((typeof callback !== "undefined") && (callback !== null) && (typeof callback === "function"))
        {
            callback(checkValue, numCheck);
        }

    },
    createXHR: function()
    {
        if (typeof XMLHttpRequest !== "undefined") {
            return new XMLHttpRequest();
        } else if (typeof ActiveXObject !== "undefined") {
            // Prior to IE 7
            if (typeof arguments.callee.activeXString !== "string") {
                var versions = ["MSXML2.XMLHttp.6.0", "MSXML2.XMLHttp.3.0", "MSXML2.XMLHttp"], i, len;
                for (i = 0, len = versions.length; i < len; i++) {
                    try {
                        new ActiveXObject(versions[i]);
                        arguments.callee.activeXString = versions[i];
                        break;
                    } catch (ex) {
                        // alert('Your browser doesn't support this feature');
                    }
                }
            }
            return new ActiveXObject(arguments.callee.activeXString);
        } else {
            throw new Error("No XHR object available.");
        }
    },
    formatFileSize: function(bytes)
    {
        if (typeof bytes !== 'number') {
            return '';
        }
        if (bytes >= (1024 * 1024 * 1024)) {
            return (bytes / (1024 * 1024 * 1024)).toFixed(0) + ' GB';
        }
        if (bytes >= (1024 * 1024)) {
            return (bytes / (1024 * 1024)).toFixed(0) + ' MB';
        }
        return (bytes / 1024).toFixed(0) + ' KB';
    }
};

var Validate = {
    validate_confirm_password: function(pwdSelector, confrmSelector, warningSelector)
    {
        var validate = true;
        var pwd = $("#" + pwdSelector);
        var confrm = $("#" + confrmSelector);
        var warning = $("#" + warningSelector);

        var warning_not_match = "<strong class='warning'>Warning!</strong> Password does not match";
        var warning_not_valid = "<strong class='warning'>Warning!</strong> Password length must be between 6 and 20";

        // Check if password and confirm pass match
        if (pwd[0].value !== confrm[0].value) {

            warning.html(warning_not_match);

            pwd[0].focus();
            warning.show();

            validate = false;
        }

        // Check the length of password
        var pwdValue = $.trim(pwd[0].value);
        // Length must be between 6 and 20
        var len = pwdValue.length;
        if (len < 6 || len > 20) {
            warning.html(warning_not_valid);
            pwd[0].focus();
            $(warning).show();
            validate = false;
        }

        return validate;
    }
};

var Global = {
    availableSpace: 0,
    startAll: false,
    resetStartAll: function()
    {
        this.startAll = false;
    },
    triggerStartAll: function()
    {
        this.startAll = true;
    }
};