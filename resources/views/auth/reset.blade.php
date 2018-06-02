@extends('layout_public')
@section('content')
<style type="text/css">
    
    .content {
    width: 400px;
    height: 300px;
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
<form method="POST" action="/password/reset">
    {!! csrf_field() !!}
    <input type="hidden" name="token" value="{{ $token }}">

    @if (count($errors) > 0)
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <div>
        Email<br>
        <input type="email" name="email" value="{{ old('email') }}" style="width:200px;">
    </div>
<br>
    <div>
        Password<br>
        <input type="password" name="password" style="width:200px;">
    </div>
<br>
    <div>
        Confirm Password<br>
        <input type="password" name="password_confirmation" style="width:200px;">
    </div>
<br>
    <div>
        <button type="submit">
            Reset Password
        </button>
    </div>
</form>
</div>
@endsection