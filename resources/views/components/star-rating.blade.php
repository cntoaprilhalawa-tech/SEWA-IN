@props(['rating' => 0, 'count' => null])
@php $r = (int) round($rating); @endphp
<span class="stars" title="{{ number_format($rating, 1) }} dari 5">{{ str_repeat('★', $r) }}{{ str_repeat('☆', 5 - $r) }}</span>@if(!is_null($count)) <span class="muted">({{ $count }})</span>@endif
