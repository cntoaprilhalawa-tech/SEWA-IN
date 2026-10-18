@extends('layouts.app')
@section('title', 'Detail Booking')

@section('content')
@php
$isOwner = auth()->id() === $booking->owner_id;
$isRenter = auth()->id() === $booking->renter_id;
$item = $booking->item;
$days = $booking->total_days ?: ($booking->start_date->diffInDays($booking->end_date) + 1);
$avatar = fn($u) => $u->avatar ? asset('storage/'.$u->avatar) : 'https://ui-avatars.com/api/?background=E8A33D&color=141d34&name='.urlencode($u->name);
@endphp
<div style="margin-bottom:14px;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
  <x-status-badge :status="$booking->status" />
  <span class="muted">Kode #{{ $booking->booking_code }} · dibuat {{ $booking->created_at->format('d M Y H:i') }}</span>
</div>

<div class="split">
  <div>
    <div class="card" style="margin-bottom:16px">
      <div class="person">
        <div class="thumb" style="width:90px;height:70px;border-radius:12px;overflow:hidden;flex:none">
          @if($item->images->first())<img src="{{ asset('storage/'.$item->images->first()->image_path) }}" alt="">@else 🏷️ @endif
        </div>
        <div>
          <div class="h2sm"><a href="{{ route('items.show', $item->id) }}">{{ $item->name }}</a></div>
          <div class="muted">Rp{{ number_format($item->price_per_day, 0, ',', '.') }} / hari</div>
        </div>
      </div>
      <div class="line"></div>
      <div class="row">
        <div class="col">
          <div class="muted" style="margin-bottom:6px">Pemilik barang</div>
          <div class="person">
            <img class="avatar" src="{{ $avatar($booking->owner) }}" alt="">
            <div><b><a href="{{ route('user.profile', $booking->owner->id) }}">{{ $booking->owner->name }}</a></b>
              <div class="muted">{{ $booking->owner->phone ?? '-' }}</div>
            </div>
          </div>
        </div>
        <div class="col">
          <div class="muted" style="margin-bottom:6px">Penyewa</div>
          <div class="person">
            <img class="avatar" src="{{ $avatar($booking->renter) }}" alt="">
            <div><b><a href="{{ route('user.profile', $booking->renter->id) }}">{{ $booking->renter->name }}</a></b>
              <div class="muted">{{ $booking->renter->phone ?? '-' }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    @if($booking->status === 'completed')
    @php
    $myReview = $booking->reviews->firstWhere('reviewer_id', auth()->id());
    $otherName = $isRenter ? $booking->owner->name : $booking->renter->name;
    @endphp
    <div class="card">
      <div class="h2sm">Ulasan</div>
      @forelse($booking->reviews->where('is_hidden', false) as $rv)
      <div style="margin-bottom:10px">
        <b>{{ $rv->reviewer->name }}</b>
        <span class="muted">{{ $rv->direction === 'owner_to_renter' ? '(pemilik → peminjam)' : '(peminjam → pemilik)' }}</span>
        <div><x-star-rating :rating="$rv->rating" /> <span class="muted">{{ $rv->created_at->format('d M Y') }}</span></div>
        <p class="muted" style="margin:6px 0 0">{{ $rv->comment }}</p>
      </div>
      @empty
      <p class="muted" style="margin:0 0 10px">Belum ada ulasan.</p>
      @endforelse

      @if(!$myReview && ($isRenter || $isOwner))
      <div class="line"></div>
      <div class="h2sm">Beri ulasan untuk {{ $otherName }}</div>
      <form action="{{ route('reviews.store', $booking->id) }}" method="POST" id="review-form">
        @csrf
        <label>Rating</label>
        <div class="starpick">
          @foreach([5,4,3,2,1] as $n)
          <input type="radio" id="star{{ $n }}" name="rating" value="{{ $n }}" {{ $n === 5 ? 'checked' : '' }}>
          <label for="star{{ $n }}" title="{{ $n }} bintang">★</label>
          @endforeach
        </div>
        @error('rating') <div class="error">{{ $message }}</div> @enderror
        <label for="comment">Komentar</label>
        <textarea id="comment" name="comment" placeholder="Ceritakan pengalamanmu bertransaksi…" required>{{ old('comment') }}</textarea>
        @error('comment') <div class="error">{{ $message }}</div> @enderror
        <button type="submit" class="btn primary" style="margin-top:14px">Kirim Ulasan</button>
      </form>
      @endif
    </div>
    @endif
  </div>

  <div class="card" style="align-self:start">
    <div class="h2sm">Rincian Sewa</div>
    <div class="stat"><span>Periode</span><b>{{ $booking->start_date->format('d M Y') }} – {{ $booking->end_date->format('d M Y') }}</b></div>
    <div class="stat"><span>Durasi</span><b>{{ $days }} hari</b></div>
    <div class="stat"><span>Biaya sewa</span><b>Rp{{ number_format($days * $booking->price_per_day, 0, ',', '.') }}</b></div>
    <div class="stat"><span>Deposit</span><b>Rp{{ number_format($booking->deposit, 0, ',', '.') }}</b></div>
    <div class="line"></div>
    <div class="stat" style="font-size:1rem"><span>Total dibayar</span><b>Rp{{ number_format($booking->total_price + $booking->deposit, 0, ',', '.') }}</b></div>
    @if($booking->status === 'completed' && $booking->returned_at)
    <div class="line"></div>
    <div class="h2sm">Pengembalian</div>
    <div class="stat"><span>Dikembalikan</span><b>{{ $booking->returned_at->format('d M Y') }}</b></div>
    <div class="stat"><span>Keterlambatan</span><b>{{ $booking->late_days }} hari</b></div>
    <div class="stat"><span>Denda</span><b>Rp{{ number_format($booking->late_fee, 0, ',', '.') }}</b></div>
    <div class="stat"><span>Status deposit</span><b>{{ $booking->deposit_status_label }}</b></div>
    @if($booking->deposit_deduction > 0)
    <div class="stat"><span>Potongan deposit</span><b>Rp{{ number_format($booking->deposit_deduction, 0, ',', '.') }}</b></div>
    @endif
    @if($booking->damage_note)
    <p class="muted" style="margin:6px 0 0">Catatan kondisi: {{ $booking->damage_note }}</p>
    @endif
    @endif
    @if($booking->note)
    <div class="line"></div>
    <div class="h2sm">Catatan penyewa</div>
    <p class="muted" style="margin:0">{{ $booking->note }}</p>
    @endif
    @if($booking->cancelled_reason)
    <div class="line"></div>
    <div class="h2sm">Alasan</div>
    <p class="muted" style="margin:0">{{ $booking->cancelled_reason }}</p>
    @endif

    @if(!in_array($booking->status, ['cancelled', 'rejected']))
    @php
    $method = $booking->payment_method ?? 'transfer';
    $payStatus = $booking->payment_status ?? 'unpaid';
    $ownerU = $booking->owner;
    @endphp
    <div class="line"></div>
    <div class="h2sm">Pembayaran</div>
    <div class="stat"><span>Metode</span><b>{{ $booking->payment_method_label }}</b></div>
    <div class="stat"><span>Status</span><x-payment-badge :status="$payStatus" /></div>
    @if($booking->paid_at)<div class="stat"><span>Dibayar pada</span><b>{{ $booking->paid_at->format('d M Y H:i') }}</b></div>@endif

    @if($payStatus !== 'paid')
    {{-- Instruksi bayar untuk penyewa --}}
    @if($isRenter)
    @if($method === 'transfer')
    <div class="card" style="background:var(--bg);margin-top:10px">
      <div class="stat"><span>Bank</span><b>{{ $ownerU->bank_name ?? '-' }}</b></div>
      <div class="stat"><span>No. rekening</span><b>{{ $ownerU->account_number ?? '-' }}</b></div>
      <div class="stat"><span>a.n.</span><b>{{ $ownerU->name }}</b></div>
    </div>
    @elseif($method === 'qris')
    <div class="card" style="background:var(--bg);margin-top:10px;text-align:center">
      @if($ownerU->qris_image)<img src="{{ asset('storage/'.$ownerU->qris_image) }}" alt="QRIS" style="max-width:240px;width:100%;border-radius:12px">
      <div class="muted" style="margin-top:6px">a.n. {{ $ownerU->name }}</div>@else<span class="muted">Pemilik belum mengunggah QRIS.</span>@endif
    </div>
    @else
    <p class="muted" style="margin-top:10px">Bayar tunai langsung ke {{ $ownerU->name }} saat barang diambil. Pemilik akan menandai pembayaran setelah menerima uang.</p>
    @endif

    @if($method !== 'cash' && $booking->status === 'pending')
    <p class="muted" style="margin-top:10px">Bayar setelah pemilik menyetujui booking. Kamu akan diberi tahu di halaman ini.</p>
    @endif

    @if($method !== 'cash' && in_array($booking->status, ['approved', 'active']) && in_array($payStatus, ['unpaid', 'rejected']))
    @if($payStatus === 'rejected')
    <p class="error" style="margin-top:8px">Bukti sebelumnya ditolak pemilik. Unggah bukti yang jelas dan sesuai nominal.</p>
    @endif
    <form action="{{ route('payments.upload', $booking->id) }}" method="POST" enctype="multipart/form-data">
      @csrf
      <label for="payment_proof">Bukti pembayaran</label>
      <input type="file" id="payment_proof" name="payment_proof" accept="image/*" required>
      @error('payment_proof') <div class="error">{{ $message }}</div> @enderror
      <button type="submit" class="btn primary" style="margin-top:10px">Kirim Bukti Pembayaran</button>
    </form>
    @endif
    @endif

    {{-- Aksi pemilik --}}
    @if($isOwner)
    @if($method === 'cash' && in_array($booking->status, ['approved', 'active']))
    <form action="{{ route('payments.cash', $booking->id) }}" method="POST" style="margin-top:10px">
      @csrf
      <button type="submit" class="btn ok block">Tandai Tunai Sudah Diterima</button>
    </form>
    @elseif($method !== 'cash' && $payStatus === 'waiting')
    <a href="{{ route('payments.show', $booking->id) }}" class="btn primary block" style="margin-top:10px">Periksa Bukti Pembayaran</a>
    @endif
    @endif
    @endif
    @endif

    <div class="line"></div>
    <div class="h2sm">Tindakan</div>
    @if($isOwner && $booking->status === 'pending')
    <form action="{{ route('bookings.approve', $booking->id) }}" method="POST" style="margin-top:8px">
      @csrf @method('PATCH')
      <button type="submit" class="btn ok block">Setujui Booking</button>
    </form>
    <details class="fold">
      <summary class="btn bad block">Tolak Booking</summary>
      <form action="{{ route('bookings.reject', $booking->id) }}" method="POST">
        @csrf @method('PATCH')
        <label for="rejectReason">Alasan penolakan</label>
        <textarea id="rejectReason" name="reason" required placeholder="Barang sedang rusak / sedang dipakai / dll…"></textarea>
        <button type="submit" class="btn bad" style="margin-top:10px">Kirim Penolakan</button>
      </form>
    </details>
    @endif
    @if($isOwner && $booking->status === 'approved')
    <form action="{{ route('bookings.activate', $booking->id) }}" method="POST" style="margin-top:8px">
      @csrf @method('PATCH')
      <button type="submit" class="btn primary block">Tandai Barang Diserahkan</button>
    </form>
    @endif
    @if($isOwner && $booking->status === 'active')
    <details class="fold" {{ $errors->hasAny(['returned_at','damage_note','deposit_deduction']) ? 'open' : '' }}>
      <summary class="btn ok block">Catat Pengembalian &amp; Selesaikan</summary>
      <form action="{{ route('bookings.complete', $booking->id) }}" method="POST">
        @csrf @method('PATCH')
        <label for="returned_at">Tanggal barang dikembalikan</label>
        {{-- PERBAIKAN: hapus max=hari ini; min=tanggal mulai sewa; default = yang terbesar antara hari ini dan tanggal mulai --}}
        <input type="date" id="returned_at" name="returned_at"
          value="{{ old('returned_at', max(now()->startOfDay(), $booking->start_date->copy()->startOfDay())->toDateString()) }}"
          min="{{ $booking->start_date->toDateString() }}" required>
        @error('returned_at') <div class="error">{{ $message }}</div> @enderror
        <p class="muted" style="margin:6px 0 0">Denda Rp{{ number_format(\App\Models\Booking::LATE_FEE_PER_DAY, 0, ',', '.') }} per hari jika melewati {{ $booking->end_date->format('d M Y') }}.</p>
        @if($booking->deposit > 0)
        <label for="deposit_deduction" style="margin-top:10px">Potongan deposit karena kerusakan (Rp, opsional)</label>
        <input type="number" id="deposit_deduction" name="deposit_deduction" min="0" max="{{ (int) $booking->deposit }}" value="{{ old('deposit_deduction', 0) }}">
        @error('deposit_deduction') <div class="error">{{ $message }}</div> @enderror
        @endif
        <label for="damage_note" style="margin-top:10px">Catatan kondisi barang (wajib jika deposit dipotong)</label>
        <textarea id="damage_note" name="damage_note" placeholder="Kondisi barang saat dikembalikan…">{{ old('damage_note') }}</textarea>
        @error('damage_note') <div class="error">{{ $message }}</div> @enderror
        <button type="submit" class="btn ok" style="margin-top:10px">Simpan &amp; Selesai</button>
      </form>
    </details>
    @endif
    @if($isRenter && in_array($booking->status, ['pending', 'approved']) && $booking->payment_status !== 'paid')
    <details class="fold">
      <summary class="btn ghost block">Batalkan Booking</summary>
      <form action="{{ route('bookings.cancel', $booking->id) }}" method="POST">
        @csrf @method('PATCH')
        <label for="cancelReason">Alasan (opsional)</label>
        <textarea id="cancelReason" name="reason" placeholder="Alasan pembatalan…"></textarea>
        <button type="submit" class="btn bad" style="margin-top:10px">Batalkan Booking</button>
      </form>
    </details>
    @endif
    @if(!(($isOwner && in_array($booking->status, ['pending','approved','active'])) || ($isRenter && in_array($booking->status, ['pending','approved']) && $booking->payment_status !== 'paid')))
    <p class="muted" style="margin:8px 0 0">Tidak ada tindakan tersedia untuk status saat ini.</p>
    @endif
    <a href="{{ route('complaints.create', ['booking' => $booking->id]) }}" class="btn ghost block" style="margin-top:10px">Ajukan Keluhan</a>
  </div>
</div>
@endsection