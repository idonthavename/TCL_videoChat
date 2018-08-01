<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>404</title>

    <link href="{{env('APP_URL')}}css/errors/error.css" rel="stylesheet" type="text/css" />

</head>
<body>


<div id="container">
    <img class="png" src="{{env('APP_URL')}}images/errors/404.png" />
    <p style="font-size: 1.5rem;color: white;">{{$exception->getMessage()}}</p>
    {{--<img class="png msg" src="images/errors/404_msg.png" />--}}
    {{--<p><a href="/" target="_self"><img class="png" src="images/errors/404_to_index.png" /></a> </p>--}}
</div>

<div id="cloud" class="png"></div>
</body>
</html>
