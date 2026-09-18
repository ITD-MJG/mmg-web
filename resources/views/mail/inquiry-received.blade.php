<h1>Permintaan Penawaran Baru</h1>

<p><strong>Nama:</strong> {{ $inquiry->name }}</p>
<p><strong>Perusahaan:</strong> {{ $inquiry->company ?: '-' }}</p>
<p><strong>Email:</strong> {{ $inquiry->email }}</p>
<p><strong>Telepon:</strong> {{ $inquiry->phone }}</p>
@if ($inquiry->product)
    <p><strong>Produk:</strong> {{ $inquiry->product->name }}</p>
@endif
<p><strong>Pesan:</strong></p>
<p>{!! nl2br(e($inquiry->message)) !!}</p>

<p>Lihat di admin: {{ url('/admin/inquiries/'.$inquiry->id) }}</p>
