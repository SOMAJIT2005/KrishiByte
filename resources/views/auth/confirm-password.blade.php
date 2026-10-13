@extends('layouts.app')
@section('title','Confirm your password')
@section('content')
<div class="card mx-auto" style="max-width:480px"><div class="card-body"><h1 class="h4">Confirm your password</h1><p>Account verification and rule changes require recent password authentication.</p><form method="POST" action="{{ route('password.confirm.store') }}">@csrf<label for="password" class="form-label">Current password</label><input class="form-control mb-3" id="password" name="password" type="password" autocomplete="current-password" required><button class="btn btn-primary">Confirm and continue</button></form></div></div>
@endsection
