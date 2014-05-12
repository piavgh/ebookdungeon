<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        {{ get_title() }}
        {{ stylesheet_link('bootstrap/css/bootstrap311.css') }}
        {{ stylesheet_link('bootstrap/css/bootstrap.css') }}
        {{ stylesheet_link('bootstrap/css/bootstrap-responsive.css') }}
        {{ stylesheet_link('css/style.css') }}
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="Your dashboard">
        <meta name="author" content="Phalcon Team">
    </head>
    <body>
        {{ content() }}
        {{ javascript_include('js/jquery.min.js') }}
        {{ javascript_include('bootstrap/js/bootstrap.js') }}
        {{ javascript_include('js/utils.js') }}
        
        <script>
 		
 		<!-- Check if is there any checkbox is checked, if not, disable the button with id=deleteBtn -->
    	$( document ).ready(function() {
        	var boxes = $('.itemCheckbox');
			boxes.on('change', function () {
    			$('#deleteBtn').prop('disabled', !boxes.filter(':checked').length);
			}).trigger('change');
    	});
		
    </script>
    </body>
</html>