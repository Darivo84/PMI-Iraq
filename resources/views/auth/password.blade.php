@extends('layout_public')
@section('content')
<style type="text/css">
    
    .content {
    width: 500px;
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
Please enter your email address below in order to reset your password:<br><br>
<form method="POST" action="/password/email">
    {!! csrf_field() !!}

    @if (count($errors) > 0)
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <div>
        Email: &nbsp;
        <input type="email" name="email" value="{{ old('email') }}">&nbsp;

        <button type="submit">
            Send Password Reset Link
        </button>
    </div>
</form>
</div>
@endsection