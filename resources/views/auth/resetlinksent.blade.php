@extends('layout_public')
@section('content')
<style type="text/css">
    
    .content {
    width: 400px;
    height: 200px;
    /*background-color: blue;*/
    
    position:absolute;
    left:0; right:0;
    top:0; bottom:0;
    margin:auto;
    
    max-width:100%;
    max-height:100%;
    overflow:auto;
    color: white;
    font-size: 20px;
}

</style>
<div class='content'>
Thank you, a password reset link has been sent to your email address.
</div>
@endsection