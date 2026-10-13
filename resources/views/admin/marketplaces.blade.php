@extends('layouts.app')
@section('title','District marketplaces')
@section('content')
<h1 class="h3">District marketplaces</h1><form class="card card-body mb-4" method="POST" action="{{ route('admin.marketplaces.store') }}">@csrf<label for="market-district">District</label><select id="market-district" name="district_id" class="form-select mb-3" required>@foreach($districts as $district)<option value="{{ $district->id }}">{{ $district->name }}</option>@endforeach</select><label for="market-name">Marketplace name</label><input id="market-name" name="name" class="form-control mb-3" required maxlength="150"><button class="btn btn-primary">Add marketplace</button></form>
<div class="table-responsive"><table class="table"><tr><th>District</th><th>Market</th><th>Data source</th></tr>@foreach($markets as $market)<tr><td>{{ $market->district->name }}</td><td>{{ $market->name }} · #{{ $market->id }}</td><td>{{ $market->is_demo ? 'Illustrative demo' : 'Administrator added' }}</td></tr>@endforeach</table></div>
@endsection
