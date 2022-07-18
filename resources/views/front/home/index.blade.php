@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                @if (Session::has('retCode'))
                    @if (Session::get('retCode') == 1)
                        <div class="alert alert-danger">
                            {{ Session::get('msj') }}
                        </div>
                    @else
                        <div class="alert alert-success">
                            {{ Session::get('msj') }}
                        </div>
                    @endif
                @endif

            </div>
            @include('front.home.searcher')
        </div>
    </div>

@endsection
