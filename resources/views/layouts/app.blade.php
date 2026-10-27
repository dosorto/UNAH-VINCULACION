@extends('layouts.panel.base')

@section('main')
    <x-flujos-responsables-pendientes />
    {{ $slot }}
@endsection
