@props(['transaction', 'participant'])
@php($movement = $transaction->movementStatusFor($participant))
@if($movement)
<span class="badge {{ $transaction->is_voided ? 'bg-secondary-subtle text-secondary' : 'bg-primary-subtle text-primary' }} text-wrap">{{ $movement }}</span>
@endif
