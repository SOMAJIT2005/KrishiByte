@props(['user'])
@if($user && $user->is_verified && $user->account_status === 'active')
<span class="badge verified-badge" title="Account reviewed by a KrishiByte administrator; product quality is assessed separately."><i class="bi bi-patch-check-fill" aria-hidden="true"></i> Verified</span>
@endif
