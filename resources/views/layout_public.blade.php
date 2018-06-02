<!DOCTYPE html>
<html>
    <head>
        <title>PMI</title>

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

        <!-- APP CONTENT -->
            <div id="main-container">
                <div class="content">
                    @yield('content')
                </div>
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
