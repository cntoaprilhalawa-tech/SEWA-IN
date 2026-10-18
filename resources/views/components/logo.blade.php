{{-- Logo SEWA-IN: label harga + huruf S. Warna teks mengikuti warna elemen induk. --}}
@props(['height' => 28, 'text' => true])
@php $w = (int) round($height * 60 / 46); @endphp
<span class="logo-lockup">
  <svg class="logo-mark" xmlns="http://www.w3.org/2000/svg" viewBox="2 9 60 46" width="{{ $w }}" height="{{ $height }}" aria-hidden="true" focusable="false">
    <path d="M20 12h36a4 4 0 0 1 4 4v32a4 4 0 0 1-4 4H20L4 32Z" fill="#E8A33D"/>
    <circle cx="17.5" cy="32" r="3.6" fill="#1E2A4A"/>
    <path transform="translate(41.5 32) scale(.9) translate(-32 -38.75)" d="M41 32C41 28 37 25.5 32 25.5S23 28 23 32c0 4.5 4.5 5.8 9 6.8s9 2.2 9 6.7c0 4-4 6.5-9 6.5s-9-2.5-9-6.5" fill="none" stroke="#1E2A4A" stroke-width="6.4" stroke-linecap="round" stroke-linejoin="round"/>
  </svg>
  @if($text)<span class="logo-word">SEWA<i>-IN</i></span>@endif
</span>
