$(document).ready(function() {
    $("#registerForm .alert").hide();
    $("#sign-in-warning").hide();
    $(".confirm").confirm({
        text: "Are you sure you want to delete that content?",
        title: "Confirmation",
        confirmButton: "Yes I am",
        cancelButton: "No",
        post: false
    });

    // Delete multiple form
    $("#deleteBtn").confirm({
        text: "Are you sure you want to delete that content?",
        title: "Confirmation",
        confirm: function(button) {
            var form = $("#contentForm");
            var tempElement = $("<input type='hidden' name='delete_content' />");
            tempElement.appendTo(form);

            form.submit();
        },
        confirmButton: "Yes I am",
        cancelButton: "No",
        post: true
    });
});

