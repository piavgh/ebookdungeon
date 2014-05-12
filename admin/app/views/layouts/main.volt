<div class="navbar navbar-fixed-top">
    <div class="navbar-inner">
        <div class="container">
            <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </a>
            {{ link_to("index/index/", 'Mediacloud | Admin Control Panel', 'class' : 'brand') }}
            {{ elements.getMenu() }}
        </div>
    </div>
</div>

<div class="container">
    {{ content() }}
    <hr>
    <footer>
        <p class="pull-left">Copyright &copy; Pacific NW Investments, Ltd. 2014, All Rights Reserved.</p>
		<p class="pull-right">Powered by <a href="http://www.agile.vn">Agile Techno Solutions</a>.</p>
    </footer>
</div>
