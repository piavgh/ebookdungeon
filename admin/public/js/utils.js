var Profile = {
	check : function(id) {
		if ($.trim($("#" + id)[0].value) == '') {
			$("#" + id)[0].focus();
			$("#" + id + "_alert").show();

			return false;
		}
		;

		return true;
	},
	validate : function() {
		if (SignUp.check("name") == false) {
			return false;
		}
		if (SignUp.check("email") == false) {
			return false;
		}
		$("#profileForm")[0].submit();
	}
};

var ChangeEmail = {
	check : function(id) {
		if ($.trim($("#" + id)[0].value) == '') {
			$("#" + id)[0].focus();
			$("#" + id + "_alert").show();

			return false;
		}
		;

		return true;
	},
	validate : function() {
		if (SignUp.check("email") == false) {
			return false;
		}
		if (SignUp.check("password") == false) {
			return false;
		}
		$("#changeemailForm")[0].submit();
	}
};

var ChangePass = {
	check : function(id) {
		if ($.trim($("#" + id)[0].value) == '') {
			$("#" + id)[0].focus();
			$("#" + id + "_alert").show();

			return false;
		}
		;

		return true;
	},
	validate : function() {
		if (SignUp.check("current_password") == false) {
			return false;
		}
		if (SignUp.check("new_password") == false) {
			return false;
		}
		if (SignUp.check("confirm_password") == false) {
			return false;
		}
		$("#changepassForm")[0].submit();
	}
};

var SignUp = {
	check : function(id) {
		if ($.trim($("#" + id)[0].value) == '') {
			$("#" + id)[0].focus();
			$("#" + id + "_alert").show();

			return false;
		}
		;

		return true;
	},
	validate : function() {
		if (SignUp.check("name") == false) {
			return false;
		}
		if (SignUp.check("username") == false) {
			return false;
		}
		if (SignUp.check("email") == false) {
			return false;
		}
		if (SignUp.check("password") == false) {
			return false;
		}
		if ($("#password")[0].value != $("#repeatPassword")[0].value) {
			$("#repeatPassword")[0].focus();
			$("#repeatPassword_alert").show();

			return false;
		}
		$("#registerForm")[0].submit();
	}
};

var Util = {
	toggle_group_field : function(event) {
		var value = event.options[event.selectedIndex].value;
		var group = $("#control-groupname");
		if (value == "individual") {
			// Hide group field
			$("#groupname").removeAttr('required');
			group.hide("slow");
		} else if (value == "group") {
			// Show group
			$("#groupname").prop('required', true);
			group.show("slow");
		}
	},
	toggle_checkbox : function(event, name) {
		var checkValue = event.checked;
		var checkboxes = document.getElementsByName(name);
		for (var i = 0; i < checkboxes.length; i++) {
			checkboxes[i].checked = checkValue;
		}
		var buttons = document.getElementsByName('modifyButton[]');
		if (checkValue) {
			for (var j = 0; j < buttons.length; j++) {
				buttons[j].removeAttribute("disabled");
			}
		} else {
			for (var j = 0; j < buttons.length; j++) {
				buttons[j].setAttribute("disabled", true);
			}
		}
	},
	toggle_individual_checkbox : function(event, id) {
		var checkValue = event.checked;
		if (checkValue) {
			var button = document.getElementById('modifyBtn' + id);
			button.removeAttribute("disabled");
		} else {
			var button = document.getElementById('modifyBtn' + id);
			button.setAttribute("disabled", true);
		}
	},
	createXHR : function() {
		if (typeof XMLHttpRequest != "undefined") {
			return new XMLHttpRequest();
		} else if (typeof ActiveXObject != "undefined") {
			// Prior to IE 7
			if (typeof arguments.callee.activeXString != "string") {
				var versions = [ "MSXML2.XMLHttp.6.0", "MSXML2.XMLHttp.3.0",
						"MSXML2.XMLHttp" ], i, len;
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
	}
};

$(document).ready(function() {
	$("#registerForm .alert").hide();
	$("div.profile .alert").hide();
});
