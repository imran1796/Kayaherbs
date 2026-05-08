@extends('admin.layouts.app')

@section('title', 'Edit Role')
@section('page_title', 'Edit Role')
@section('page_subtitle', 'Update role naming and permission assignment.')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">Roles</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit</li>
@endsection

@section('content')
    <form method="post" action="{{ route('admin.roles.update', $role->id) }}">
        @csrf
        @method('put')
        @include('user::roles._form', [
            'title' => 'Edit Role',
            'button' => 'Update role',
            'permissionMatrix' => $permissionMatrix,
        ])
    </form>
@endsection
