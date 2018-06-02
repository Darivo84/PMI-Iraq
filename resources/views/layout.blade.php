<!DOCTYPE html>
<html>
    <head>
        <title>Laravel</title>

        <!-- SCRIPTS -->
            <script src="/scripts/jquery-1.11.2.min.js"></script>
            <script src="/scripts/functions.js"></script>

        <!-- FONTS -->
            <link href='https://fonts.googleapis.com/css?family=Lato:300' rel='stylesheet' type='text/css'>
            <link href='https://fonts.googleapis.com/css?family=Quicksand' rel='stylesheet' type='text/css'>

        <!-- STYLES -->
            <link rel="stylesheet" type="text/css" href="/css/style.css">
            <link rel="stylesheet" type="text/css" href="/css/font-awesome.min.css">

    </head>
    <body>

        <!-- PAGE PRE LOADER -->
            <div class="loader-view">
                <div class="loader-block">
                    <div class="loader"></div>
                    <div>Loading...</div>
                </div>
            </div>

        <!-- APP NAV -->
            <div id="main-nav">
                <div class="icon-word-block">
                    <span class="fa fa-group fa-2x icon"></span>
                    <br>
                    <span class="word">Users</span>
                    <div class="nav-block">
                        <a href="{{URL::route('user_add')}}" data-load><button class="icon-sub-word-block">
                            <span class="fa fa-user-plus fa-2x icon"></span>
                            <br>
                            <span class="word">Add</span>
                        </button></a>
                        <button class="icon-sub-word-block">
                            <span class="fa fa-edit fa-2x icon"></span>
                            <br>
                            <span class="word">Log</span>
                        </button>                         
                    </div>
                </div>
                <div class="icon-word-block">
                    <span class="fa fa-edit fa-2x icon"></span>
                    <br>
                    <span class="word">Logs</span>
                    <div class="nav-block">
                        <button class="icon-sub-word-block">
                            <span class="fa fa-group fa-2x icon"></span>
                            <br>
                            <span class="word">Users</span>
                        </button>
                        <button class="icon-sub-word-block">
                            <span class="fa fa-exchange fa-2x icon"></span>
                            <br>
                            <span class="word">Requests</span>
                        </button>                        
                    </div>
                </div>                
            </div>

        <!-- APP CONTENT -->
            <div id="main-container">
                @yield('content')
            </div>
        <br class="clear">

        <script type="text/javascript">
            // LOADER
                $('[data-load]').on('click',function(){
                    $(".loader-view").fadeIn("slow");
                });               

                $(window).load(function() {
                    $(".loader-view").fadeOut("slow");
                });

        </script>
                
    </body>
</html>
