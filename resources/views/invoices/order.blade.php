<x-app-layout>
    <div class="max-w-[800px] mx-auto p-8 border border-gray-200 shadow-md text-base leading-relaxed text-gray-700">
        <table class="w-full leading-inherit text-left border-collapse">
            <tr class="top">
                <td colspan="4" class="p-1 align-top">
                    <table class="w-full leading-inherit text-left border-collapse">
                        <tr>
                            <td class="p-1 align-top pb-5 text-5xl leading-tight text-gray-800">
                                {{ config('app.name', 'My Tree Enviros') }}
                            </td>
                            <td class="p-1 align-top pb-5 text-right">
                                Invoice #: {{ $order->reference_number }}<br>
                                Created: {{ $order->created_at->format('F j, Y') }}<br>
                                Status: {{ ucfirst($order->status->value) }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr class="information">
                <td colspan="4" class="p-1 align-top">
                    <table class="w-full leading-inherit text-left border-collapse">
                        <tr>
                            <td class="p-1 align-top pb-10">
                                My Tree Enviros<br>
                                123 Green Street<br>
                                Eco City, Earth<br>
                                GSTIN: 36AASCM8155B1Z7
                            </td>
                            <td class="p-1 align-top pb-10 text-right">
                                {{ $order->user->name }}<br>
                                {{ $order->user->email }}<br>
                                @if ($order->shippingAddress)
                                    {{ $order->shippingAddress->address_line_1 }}<br>
                                    {{ $order->shippingAddress->city }}, {{ $order->shippingAddress->state }}
                                    {{ $order->shippingAddress->postal_code }}
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr class="heading">
                <td class="p-1 align-top bg-gray-100 border-b border-gray-300 font-bold">Item</td>
                <td class="p-1 align-top bg-gray-100 border-b border-gray-300 font-bold text-center">Quantity</td>
                <td class="p-1 align-top bg-gray-100 border-b border-gray-300 font-bold text-right">Unit Price</td>
                <td class="p-1 align-top bg-gray-100 border-b border-gray-300 font-bold text-right">Amount</td>
            </tr>

            @foreach ($order->items as $item)
                <tr class="item">
                    <td class="p-1 align-top border-b border-gray-200">
                        @if ($item->tree)
                            {{ $item->tree->name }}
                            @if ($item->planPrice && $item->planPrice->plan)
                                <br><span class="text-sm text-gray-600">({{ $item->planPrice->plan->duration }}
                                    {{ $item->planPrice->plan->duration > 1 ? 'Years' : 'Year' }} Plan)</span>
                            @endif
                        @elseif($item->type === 'campaign')
                            Campaign Donation
                        @elseif($item->productVariant)
                            {{ $item->productVariant->inventory->product->name ?? 'Product' }}
                        @else
                            {{ ucfirst($item->type) }}
                        @endif
                    </td>
                    <td class="p-1 align-top border-b border-gray-200 text-center">{{ $item->quantity }}</td>
                    <td class="p-1 align-top border-b border-gray-200 text-right">
                        ₹{{ number_format($item->amount, 2) }}</td>
                    <td class="p-1 align-top border-b border-gray-200 text-right">
                        ₹{{ number_format($item->total_amount, 2) }}</td>
                </tr>
            @endforeach

            <tr class="total">
                <td class="p-1 align-top"></td>
                <td class="p-1 align-top"></td>
                <td class="p-1 align-top text-right pt-2 border-t-2 border-gray-200 font-bold">Subtotal:</td>
                <td class="p-1 align-top text-right pt-2 border-t-2 border-gray-200 font-bold">
                    ₹{{ number_format($order->subtotal, 2) }}</td>
            </tr>
            @if ($order->total_discount > 0)
                <tr class="total">
                    <td class="p-1 align-top"></td>
                    <td class="p-1 align-top"></td>
                    <td class="p-1 align-top text-right pt-2 font-bold">Discount:</td>
                    <td class="p-1 align-top text-right pt-2 font-bold">
                        -₹{{ number_format($order->total_discount, 2) }}</td>
                </tr>
            @endif
            @if ($order->total_tax > 0)
                <tr class="total">
                    <td class="p-1 align-top"></td>
                    <td class="p-1 align-top"></td>
                    <td class="p-1 align-top text-right pt-2 font-bold">Tax:</td>
                    <td class="p-1 align-top text-right pt-2 font-bold">
                        ₹{{ number_format($order->total_tax, 2) }}</td>
                </tr>
            @endif
            @if ($order->total_shipping > 0)
                <tr class="total">
                    <td class="p-1 align-top"></td>
                    <td class="p-1 align-top"></td>
                    <td class="p-1 align-top text-right pt-2 font-bold">Shipping:</td>
                    <td class="p-1 align-top text-right pt-2 font-bold">
                        ₹{{ number_format($order->total_shipping, 2) }}</td>
                </tr>
            @endif
            @if ($order->total_fee > 0)
                <tr class="total">
                    <td class="p-1 align-top"></td>
                    <td class="p-1 align-top"></td>
                    <td class="p-1 align-top text-right pt-2 font-bold">Fee:</td>
                    <td class="p-1 align-top text-right pt-2 font-bold">
                        ₹{{ number_format($order->total_fee, 2) }}</td>
                </tr>
            @endif
            <tr class="total">
                <td class="p-1 align-top"></td>
                <td class="p-1 align-top"></td>
                <td class="p-1 align-top text-right pt-2 border-t-2 border-gray-200 font-bold"><strong>Total:</strong>
                </td>
                <td class="p-1 align-top text-right pt-2 border-t-2 border-gray-200 font-bold">
                    <strong>₹{{ number_format($order->grand_total, 2) }}</strong>
                </td>
            </tr>
        </table>
        <footer class="mt-12 text-center text-xs text-gray-500">
            <p>Thank you for your business!</p>
            <p>Payment Method: {{ $order->payment_method ?? 'N/A' }}</p>
        </footer>
    </div>
</x-app-layout>
