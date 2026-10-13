@extends('layouts.app')
@section('title','Farmer Enrollment & Rating Review')
@section('content')
<h1 class="h2 mb-4">Farmer enrollment & rating review</h1>
<h2 class="h4">Pending enrollment applications</h2>
@forelse($enrollments as $entry)
<div class="card card-body mb-3"><h3 class="h5">{{ $entry->name }} · {{ $entry->district->name }}</h3><p>Private contact: {{ $entry->phone }}</p><p>{{ $entry->cultivation_notes }}</p><div class="small text-muted mb-2">Reference {{ $entry->tracking_code }} · Declared crop IDs: {{ implode(', ',$entry->grown_crop_ids) }}</div><form method="POST" action="{{ route('admin.farmer-enrollment.review',$entry->id) }}" class="d-flex flex-wrap gap-2">@csrf<label class="visually-hidden" for="enrollment-reason-{{ $entry->id }}">Enrollment review reason</label><input class="form-control" id="enrollment-reason-{{ $entry->id }}" name="reason" placeholder="Identity/contact check and review reason" minlength="5" maxlength="500" required><button name="decision" value="approved" class="btn btn-success">Approve & generate profile</button><button name="decision" value="rejected" class="btn btn-outline-danger">Reject</button></form></div>
@empty
<p class="text-muted">No applications waiting.</p>
@endforelse
{{ $enrollments->links() }}
<h2 class="h4 mt-4">Pending ratings</h2>
@forelse($ratings as $rating)
<div class="card card-body mb-3"><h3 class="h5">{{ $rating->farmer->name }} · {{ $rating->score }}/5 · {{ $rating->reviewer_group }}</h3><p>{{ $rating->comment }}</p><form method="POST" action="{{ route('admin.farmer-rating.review',$rating->id) }}" class="d-flex flex-wrap gap-2">@csrf<label class="visually-hidden" for="rating-reason-{{ $rating->id }}">Rating moderation reason</label><input id="rating-reason-{{ $rating->id }}" class="form-control" name="reason" placeholder="Moderation reason" minlength="5" maxlength="500" required><button name="decision" value="approved" class="btn btn-success">Publish rating</button><button name="decision" value="rejected" class="btn btn-outline-danger">Reject</button></form></div>
@empty
<p class="text-muted">No ratings waiting.</p>
@endforelse
{{ $ratings->links() }}
@endsection
