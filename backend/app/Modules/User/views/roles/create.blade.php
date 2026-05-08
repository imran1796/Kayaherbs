@extends('admin.layouts.app')

@section('title', 'Create Role')
@section('page_title', 'Create Role')
@section('page_subtitle', 'Create an access role before assigning permissions.')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">Roles</a></li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('content')
    <form method="post" action="{{ route('admin.roles.store') }}">
        @csrf
        @include('user::roles._form', [
            'title' => 'Create Role',
            'button' => 'Save role',
            'permissionMatrix' => $permissionMatrix,
        ])
    </form>
@endsection
