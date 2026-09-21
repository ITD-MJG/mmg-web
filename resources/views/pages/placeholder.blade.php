@extends('layouts.app')

@section('title', \App\Models\Setting::get('default_meta_title', 'Medquest Mitra Global'))

@section('content')
    <section class="mx-auto max-w-shell px-4 py-24">
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">
            {{ \App\Models\Setting::get('company_name', 'Medquest Mitra Global') }}
        </h1>
    </section>
@endsection
