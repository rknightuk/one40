@extends('layouts.main')

@section('content')

    <form method="POST" action="/login">
        {!! csrf_field() !!}

        <div class="form-group">
            <label for="email">Email address</label>
            <input type="email" class="form-control" name="email" value="{{ old('email') }}" placeholder="Email">
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" class="form-control" name="password" placeholder="Password">
        </div>
        <div class="checkbox">
            <label>
                <input type="checkbox" name="remember"> Remember me
            </label>
        </div>
        <button type="submit" class="btn btn-default">Submit</button>
    </form>

@endsection