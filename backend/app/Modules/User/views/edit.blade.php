@extends('admin.layouts.app')

@section('title', 'Edit User')
@section('page_title', 'Edit User')
@section('page_subtitle', 'Update a platform user account.')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit</li>
@endsection

@section('content')
    <form method="post" action="{{ route('admin.users.update', $user->id) }}" class="ajax-user-form" data-redirect="{{ route('admin.users.index') }}">
        @csrf
        @method('put')
        @include('user::_form', [
            'title' => 'User details',
            'button' => 'Update user',
            'requirePassword' => false,
        ])
    </form>
@endsection
