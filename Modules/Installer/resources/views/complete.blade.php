@extends('installer::app')
@section('content')
    <div class="text-center card">
        <div class="card-header d-flex justify-content-between">
            <p>Setup Complete</p>
            <a class="btn btn-outline-primary" href="{{ route('setup.smtp') }}">&laquo; Back</a>
        </div>
        <div class="card-body">
            <h3 class="py-5 text-success">Congratulations! Installation is complete.</h3>
            <div class="d-flex justify-content-center">
                <form action="{{ route('website.completed', 'admin') }}" method="GET" class="p-2">
                    @csrf
                    <button type="submit" class="btn btn-primary">Visit Dashboard</button>
                </form>
                <form action="{{ route('website.completed', 'home') }}" method="GET" class="p-2">
                    @csrf
                    <button type="submit" class="btn btn-success">Visit Website</button>
                </form>
            </div>
        </div>
        <div class="card-footer">
            <p style="text-align:center; font-weight:bold;"></br>Nulled by J.C. from <a href="https://babia.to" target="_self">babiato</a></p>
        </div>
    </div>
@endsection