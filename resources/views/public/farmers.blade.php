@extends('layouts.app')
@section('title','Farmer Profiles')
@section('content')
<div class="d-flex justify-content-between flex-wrap gap-3 mb-4"><div><h1 class="fw-bold">Meet the farmers</h1><p class="text-muted mb-0">Crop records, sales history, and reviewed community feedback.</p></div><a class="btn btn-primary align-self-center" href="{{ route('farmers.enroll') }}">Apply for farmer enrollment</a></div>
<form class="d-flex gap-2 mb-4"><label class="visually-hidden" for="search">Farmer name</label><input id="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search farmer name"><button class="btn btn-outline-primary">Search</button></form>
<div class="row g-3 mb-4">
@forelse($farmers as $farmer)
<div class="col-md-6 col-lg-4"><div class="card card-body h-100"><span class="text-muted small">FARMER #{{ $farmer->id }}</span><h2 class="h5 mt-2">{{ $farmer->name }} <x-verified-badge :user="$farmer" /></h2><p>{{ $farmer->district?->name ?? 'District not provided' }}</p><a class="btn btn-outline-primary" href="{{ route('farmers.show',$farmer->id) }}">View crops & ratings</a></div></div>
@empty
<p>No public farmer profiles match this search.</p>
@endforelse
</div>{{ $farmers->links() }}
@endsection
