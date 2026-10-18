{{-- Widget "Verifikasi bahwa saya manusia". Jawabannya divalidasi server (App\Services\HumanCheck). --}}
<div class="human @error('human') has-error @enderror" data-human data-state="idle" data-url="{{ route('human.challenge') }}">
  <input type="hidden" name="human" value="">
  <button type="button" class="human-box" role="checkbox" aria-checked="false">
    <span class="human-check" aria-hidden="true">
      <span class="human-spin"></span>
      <svg viewBox="0 0 24 24" fill="none" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
    </span>
    <span class="human-label"><b data-human-text>Verifikasi bahwa saya manusia</b><small data-human-sub></small></span>
    <span class="human-badge" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4.5 6v5.5c0 4.6 3.1 8.2 7.5 9.5 4.4-1.3 7.5-4.9 7.5-9.5V6L12 3Z"/><path d="m9 12 2.2 2.2L15.2 10"/></svg>
      <span>SEWA-IN<br>Keamanan</span>
    </span>
  </button>
  <p class="err" role="alert" data-human-msg>@error('human'){{ $message }}@enderror</p>
  <noscript><p class="err">Aktifkan JavaScript di browser untuk menyelesaikan verifikasi.</p></noscript>
</div>
