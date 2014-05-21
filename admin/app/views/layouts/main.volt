<div class="navbar navbar-fixed-top">
    <div class="navbar-inner">
        <div class="container">
            <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </a>
            {{ link_to("index/index/", 'E-book Dungeon | Admin Control Panel', 'class' : 'brand') }}
            {{ elements.getMenu() }}
        </div>
    </div>
</div>

<div class="container">
    {{ content() }}
    <hr>
    <footer>
        <p class="pull-left">Copyright &copy; 2014, All Rights Reserved.</p>
    </footer>
</div>
