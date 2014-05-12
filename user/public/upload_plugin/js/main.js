/*
 * jQuery File Upload Plugin JS Example 8.9.1
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

/* global $, window */

$(function() {
    'use strict';

    // Initialize the jQuery File Upload widget:
    $('#fileupload').fileupload({
        // Uncomment the following to send cross-domain cookies:
        //xhrFields: {withCredentials: true},
        dataType: 'xml',
        submit: function(e, data) {
            var file = data.files[0];

            // Checking file size
            if (file.size > Global.availableSpace) {
                // Update context
                var errors = "Available space is not enough";
                var message = document.createElement("div");
                
                var context = data.context[0];
                var td = context.getElementsByTagName('td')[2];
                var div = td.getElementsByTagName('div')[0];

                file.error = true;
                $(message).html("<div><span class=\"label label-danger\">Error</span> " + errors + "</div>");
                
                td.replaceChild(message, div);
                return false;
            }

        },
        done: function(e, data) {
            var numFile = data.getNumberOfFiles();
            this.doneFiles = (typeof this.doneFiles === 'undefined') ? 1 : (this.doneFiles + 1);

            var errors = data.result.getElementsByTagName('error');
            var file = data.files[0];
            var message = document.createElement("div");
            
            var context = data.context[0];
            var td = context.getElementsByTagName('td')[2];
            var div = td.getElementsByTagName('div')[0];

            if (errors.length > 0) {
                file.error = true;
                $(message).html("<div><span class=\"label label-danger\">Error</span> " + errors[0].textContent + "</div>");
            } else {
                $(message).html("<div><span class=\"label label-success\">Success</span></div>");
            }
            td.replaceChild(message, div);


            if ((Global.startAll === false) || (this.doneFiles >= numFile))
            {
                // Call the converter trigger
                var xhr = Util.createXHR();
                xhr.open("post", "/contents/convert", true);  // Send post request synchronous
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.send("trigger_converter=1");

            }

            // Refresh page
            if ((Global.startAll === true) && (this.doneFiles >= numFile))
            {
                Global.resetStartAll();
                window.location.replace("/contents/index");
            }

        }
    });
});
