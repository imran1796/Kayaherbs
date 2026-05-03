@extends('admin.layouts.app')

@section('title', 'Create User')
@section('page_title', 'Create User')
@section('page_subtitle', 'Create a platform user account.')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('content')
    <form method="post" action="{{ route('admin.users.store') }}" class="ajax-user-form" data-redirect="{{ route('admin.users.index') }}">
        @csrf
        @include('user::_form', [
            'title' => 'User details',
            'button' => 'Create user',
            'requirePassword' => true,
        ])
    </form>
@endsection
