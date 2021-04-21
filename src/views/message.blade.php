@extends('pragmarx/tracker::html')

@section('body')
    <div class="container">
        <div class="jumbotron">
            <h1>{{ $message }}</h1>
            <br>
            <p>
                <!--<a class="btn btn-lg btn-primary" href="/" role="button">@lang('tracker::tracker.go_home')</a>-->
            </p>
            <p>
                <a class="btn btn-lg btn-primary" href="{{url('login')}}" role="button">@lang('tracker::tracker.login')</a>
            </p>
        </div>
    </div>
@stop
