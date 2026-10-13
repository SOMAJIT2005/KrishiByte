@extends('layouts.app')
@section('title','Farmer Enrollment')
@section('content')
<div class="card card-body mx-auto p-4" style="max-width:760px"><h1 class="h2">Farmer enrollment / কৃষক নিবন্ধন</h1><p class="text-muted">A collector or family member can help complete this form. No email, password, or farmer dashboard is required. An administrator reviews the application before publishing a profile.</p>
<form method="POST" action="{{ route('farmers.enroll.store') }}">@csrf
<label for="name" class="form-label">Farmer name / কৃষকের নাম</label><input id="name" name="name" class="form-control mb-3" value="{{ old('name') }}" maxlength="150" required>
<label for="phone" class="form-label">Private contact phone / মোবাইল নম্বর</label><input id="phone" name="phone" type="tel" class="form-control mb-3" value="{{ old('phone') }}" maxlength="20" required>
<label for="district_id" class="form-label">District / জেলা</label><select id="district_id" name="district_id" class="form-select mb-3" required><option value="">Choose district</option>@foreach($districts as $district)<option value="{{ $district->id }}" @selected(old('district_id') == $district->id)>{{ $district->name }}</option>@endforeach</select>
<fieldset class="mb-3"><legend class="h6">Crops grown / উৎপাদিত ফসল</legend><div class="row g-2">@foreach($crops as $crop)<label class="col-sm-6"><input type="checkbox" name="grown_crop_ids[]" value="{{ $crop->id }}" @checked(in_array($crop->id,old('grown_crop_ids',[])))> {{ $crop->name }}</label>@endforeach</div></fieldset>
<label for="cultivation_notes" class="form-label">Growing details (public, optional) / চাষের বিবরণ</label><textarea id="cultivation_notes" name="cultivation_notes" class="form-control mb-3" maxlength="1000" rows="3">{{ old('cultivation_notes') }}</textarea>
<label class="d-block mb-3"><input name="public_profile_consent" type="checkbox" value="1" required> The farmer agrees to publish their name, district, crop details, recorded sales and moderated ratings. Phone and agreements stay private.</label>
<button class="btn btn-primary w-100">Submit enrollment / আবেদন জমা দিন</button></form></div>
@endsection
