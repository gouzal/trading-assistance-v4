<div
    x-data="{
        open: false,
        symbol: '',
        name: '',
        price: 0,
        side: 'buy',
        orderClass: 'market',
        quantity: 1,
        limitPrice: '',
        submitting: false,
        error: null,
        success: null,
        get totalValue() {
            return (this.quantity * this.price).toFixed(2);
        },
        init() {
            window.addEventListener('open-trade-modal', (e) => {
                this.symbol = e.detail.symbol;
                this.name = e.detail.name;
                this.price = parseFloat(e.detail.price) || 0;
                this.side = e.detail.side || 'buy';
                this.quantity = 1;
                this.orderClass = 'market';
                this.limitPrice = '';
                this.error = null;
                this.success = null;
                this.open = true;
            });
        },
        async placeOrder() {
            this.submitting = true;
            this.error = null;
            try {
                const res = await fetch('{{ route('orders.place') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        symbol: this.symbol,
                        order_type: this.side,
                        order_class: this.orderClass,
                        quantity: this.quantity,
                        limit_price: this.orderClass === 'limit' ? this.limitPrice : null,
                    }),
                });
                const data = await res.json();
                if (data.success) {
                    this.success = 'Order submitted successfully!';
                    setTimeout(() => { this.open = false; }, 1500);
                } else {
                    this.error = data.message || 'Order failed.';
                }
            } catch(e) {
                this.error = 'Network error. Please try again.';
            } finally {
                this.submitting = false;
            }
        }
    }"
    x-show="open"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @keydown.escape.window="open = false"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
    style="display: none;">

    <div @click.stop
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="w-full max-w-lg bg-surface-container-lowest rounded-xl shadow-2xl border border-outline-variant overflow-hidden">

        {{-- Modal Header --}}
        <div class="p-6 border-b border-outline-variant flex justify-between items-start">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <h2 class="text-2xl font-bold text-on-surface leading-none" x-text="symbol"></h2>
                    <span class="text-sm font-medium px-2 py-0.5 bg-surface-variant text-on-surface-variant rounded" x-text="name"></span>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold text-on-surface" x-text="'$' + parseFloat(price).toFixed(2)"></span>
                </div>
            </div>
            <button @click="open = false" class="p-2 hover:bg-surface-container rounded-full transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        {{-- Modal Content --}}
        <div class="p-6 space-y-6">

            {{-- Error / Success --}}
            <div x-show="error" class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm" x-text="error"></div>
            <div x-show="success" class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm" x-text="success"></div>

            {{-- Buy/Sell Tabs --}}
            <div class="flex p-1 bg-surface-container-high rounded-lg">
                <button @click="side = 'buy'"
                    :class="side === 'buy' ? 'bg-green-600 text-white shadow-sm' : 'text-on-surface-variant hover:text-on-surface'"
                    class="flex-1 py-2 text-center font-bold rounded-md transition-all">Buy</button>
                <button @click="side = 'sell'"
                    :class="side === 'sell' ? 'bg-red-600 text-white shadow-sm' : 'text-on-surface-variant hover:text-on-surface'"
                    class="flex-1 py-2 text-center font-bold rounded-md transition-all">Sell</button>
            </div>

            {{-- Order Type --}}
            <div class="space-y-2">
                <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Order Type</label>
                <div class="grid grid-cols-2 gap-4">
                    <button @click="orderClass = 'market'"
                        :class="orderClass === 'market' ? 'border-primary bg-primary-fixed-dim/10 text-primary' : 'border-outline-variant text-on-surface-variant hover:border-outline'"
                        class="flex items-center justify-center gap-2 py-3 px-4 border-2 rounded-xl font-semibold transition-all">
                        <span class="material-symbols-outlined text-sm" x-show="orderClass === 'market'" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                        Market
                    </button>
                    <button @click="orderClass = 'limit'"
                        :class="orderClass === 'limit' ? 'border-primary bg-primary-fixed-dim/10 text-primary' : 'border-outline-variant text-on-surface-variant hover:border-outline'"
                        class="flex items-center justify-center gap-2 py-3 px-4 border-2 rounded-xl font-semibold transition-all">
                        <span class="material-symbols-outlined text-sm" x-show="orderClass === 'limit'" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                        Limit
                    </button>
                </div>
            </div>

            {{-- Limit Price (shown only for limit orders) --}}
            <div x-show="orderClass === 'limit'" class="space-y-2">
                <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Limit Price ($)</label>
                <input type="number" step="0.01" min="0.01"
                    x-model="limitPrice"
                    class="w-full h-12 bg-surface-container border-2 border-transparent focus:border-primary rounded-xl px-4 text-on-surface focus:ring-0 transition-all outline-none"
                    placeholder="0.00">
            </div>

            {{-- Quantity --}}
            <div class="space-y-3">
                <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Quantity (Shares)</label>
                <div class="relative">
                    <input type="number" min="1" x-model="quantity"
                        class="w-full h-14 bg-surface-container border-2 border-transparent focus:border-primary rounded-xl px-4 text-xl font-bold text-on-surface focus:ring-0 transition-all outline-none"
                        placeholder="0">
                    <div class="absolute right-4 top-1/2 -translate-y-1/2 flex items-center gap-1">
                        <button @click="quantity = Math.max(1, quantity - 1)" class="p-1 hover:bg-outline-variant/20 rounded text-on-surface">
                            <span class="material-symbols-outlined">remove</span>
                        </button>
                        <button @click="quantity = parseInt(quantity) + 1" class="p-1 hover:bg-outline-variant/20 rounded text-on-surface">
                            <span class="material-symbols-outlined">add</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Summary --}}
            <div class="bg-surface-container-low rounded-xl p-4 space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-on-surface-variant">Price / Share</span>
                    <span class="text-sm font-bold text-on-surface" x-text="'$' + parseFloat(price).toFixed(2)"></span>
                </div>
                <div class="pt-3 border-t border-outline-variant/30 flex justify-between items-center">
                    <span class="text-base font-bold text-on-surface">Total Order Value</span>
                    <span class="text-lg font-extrabold text-primary" x-text="'$' + totalValue"></span>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="p-6 pt-0 flex flex-col sm:flex-row gap-3">
            <button @click="placeOrder()"
                :disabled="submitting"
                :class="side === 'buy' ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-red-600 hover:bg-red-700 text-white'"
                class="flex-1 py-4 font-extrabold text-lg rounded-xl shadow-lg active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!submitting" x-text="side === 'buy' ? 'Confirm Buy' : 'Confirm Sell'"></span>
                <span x-show="submitting">Submitting...</span>
            </button>
            <button @click="open = false"
                class="flex-1 py-4 bg-surface-variant text-on-surface-variant font-bold text-lg rounded-xl hover:bg-outline-variant transition-colors">
                Cancel
            </button>
        </div>
    </div>
</div>
