@extends('layouts.app')
@section('title', 'Order History')

@section('content')
<div class="max-w-4xl mx-auto p-4 md:p-8">

    {{-- Notification Alert Ledger --}}
    @if($notificationResponses->isNotEmpty())
    <div class="mb-10">
        <h3 class="text-lg font-bold text-on-surface mb-3 flex items-center gap-2">
            <span class="material-symbols-outlined text-orange-500">notifications</span>
            Notification Alerts
        </h3>
        <div class="overflow-x-auto rounded-xl border border-outline-variant/30 shadow-sm">
            <table class="w-full text-sm">
                <thead class="bg-surface-container text-on-surface-variant text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3 text-left">Company</th>
                        <th class="px-4 py-3 text-left">Action</th>
                        <th class="px-4 py-3 text-left">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20 bg-surface-container-low">
                    @foreach($notificationResponses as $response)
                    <tr class="hover:bg-surface-container transition-colors">
                        <td class="px-4 py-3 font-semibold text-on-surface">
                            {{ $response->display_label }}
                        </td>
                        <td class="px-4 py-3">
                            @if($response->action === 'buy')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-primary-container text-on-primary-container">
                                    <span class="material-symbols-outlined text-xs" style="font-size:12px">trending_up</span> BUY
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-surface-container text-on-surface-variant">
                                    <span class="material-symbols-outlined text-xs" style="font-size:12px">do_not_disturb</span> DISMISSED
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-on-surface-variant">{{ $response->created_at->format('M j, Y · H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="mb-8">
        <h2 class="text-3xl font-extrabold text-on-surface tracking-tight mb-6">Order History</h2>
        <div class="flex flex-wrap gap-2 pb-2">
            <a href="{{ route('orders.history') }}"
               class="px-5 py-2.5 rounded-full {{ !request('status') ? 'bg-primary text-on-primary shadow-md' : 'bg-surface-container text-on-surface-variant hover:bg-surface-variant' }} text-sm font-semibold transition-all">
                All Trades
            </a>
            @foreach(['filled', 'submitted', 'pending', 'cancelled', 'failed'] as $status)
            <a href="{{ route('orders.history', ['status' => $status]) }}"
               class="px-5 py-2.5 rounded-full {{ request('status') === $status ? 'bg-primary text-on-primary shadow-md' : 'bg-surface-container text-on-surface-variant hover:bg-surface-variant' }} text-sm font-semibold transition-all">
                {{ ucfirst($status) }}
            </a>
            @endforeach
        </div>
    </div>

    <div class="space-y-4">
        @forelse($orders as $order)
        @php
            $statusColor = match($order->status) {
                'filled'    => 'text-emerald-600',
                'submitted', 'pending' => 'text-orange-600',
                'cancelled' => 'text-on-surface-variant',
                'failed'    => 'text-error',
                default     => 'text-on-surface-variant',
            };
            $statusDot = match($order->status) {
                'filled'    => 'bg-emerald-500',
                'submitted', 'pending' => 'bg-orange-500',
                'cancelled' => 'bg-outline',
                'failed'    => 'bg-error',
                default     => 'bg-outline',
            };
            $isBuy = $order->order_type === 'buy';
        @endphp
        <div class="{{ $order->status === 'cancelled' ? 'opacity-75 hover:opacity-100' : '' }} bg-surface-container-low border border-outline-variant/30 rounded-xl p-4 md:p-6 shadow-sm hover:shadow-md transition-all">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-lg bg-surface-container-highest flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary">{{ $isBuy ? 'trending_up' : 'trending_down' }}</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-lg text-on-surface">{{ $order->symbol }}</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-black {{ $isBuy ? 'bg-primary-container text-on-primary-container' : 'bg-secondary-container text-on-secondary-container' }}">
                                {{ strtoupper($order->order_type) }}
                            </span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-surface-container text-on-surface-variant">
                                {{ strtoupper($order->order_class) }}
                            </span>
                        </div>
                        <p class="text-sm text-on-surface-variant">{{ $order->created_at->format('M j, Y • H:i:s') }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-8 flex-grow md:justify-end text-right">
                    <div>
                        <p class="text-[10px] uppercase tracking-wider text-outline font-bold">Qty</p>
                        <p class="font-semibold text-on-surface">{{ $order->quantity }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-wider text-outline font-bold">Price</p>
                        <p class="font-semibold text-on-surface">
                            {{ $order->executed_price ? '$'.number_format($order->executed_price, 2) : ($order->limit_price ? '$'.number_format($order->limit_price, 2) : 'Market') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-wider text-outline font-bold">Total</p>
                        <p class="font-semibold text-on-surface">
                            {{ $order->executed_price ? '$'.number_format($order->executed_price * $order->quantity, 2) : '—' }}
                        </p>
                    </div>
                    <div class="flex flex-col items-end">
                        <p class="text-[10px] uppercase tracking-wider text-outline font-bold">Status</p>
                        <span class="flex items-center gap-1 {{ $statusColor }} font-bold text-sm">
                            <span class="w-2 h-2 rounded-full {{ $statusDot }}"></span>
                            {{ strtoupper($order->status) }}
                        </span>
                    </div>
                </div>
            </div>
            @if($order->error_message)
            <div class="mt-3 text-xs text-error bg-error-container/30 rounded-lg px-3 py-2">
                {{ $order->error_message }}
            </div>
            @endif
        </div>
        @empty
        <div class="text-center py-20 text-on-surface-variant">
            <span class="material-symbols-outlined text-5xl mb-4 block">receipt_long</span>
            <p class="text-lg font-medium">No orders yet.</p>
        </div>
        @endforelse
    </div>

    @if($orders->hasPages())
    <div class="mt-10 flex justify-center">
        {{ $orders->links() }}
    </div>
    @endif
</div>

@push('scripts')
<script>
(function () {
    const params = new URLSearchParams(window.location.search);
    const action = params.get('notify_action');
    const symbol = params.get('symbol');
    const days   = params.get('days');

    if (!action || !symbol) return;

    // Record the response then clean the URL
    fetch('/api/push/response', {
        method:  'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ action, symbol, days: days ? parseInt(days, 10) : null }),
    }).finally(() => {
        // Remove query params from URL without reloading
        const clean = window.location.pathname;
        window.history.replaceState({}, '', clean);
        // Reload so the ledger table shows the new row
        window.location.reload();
    });
})();
</script>
@endpush
@endsection
