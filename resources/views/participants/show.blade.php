<x-layouts.app title="{{ $participant->name }}">
    @php
        $tabs = [
            'participant' => 'Participant Details',
            'payment' => 'Payment Details',
            'ticket' => 'Ticket details',
        ];
    @endphp

    <div class="mb-6">
        <a href="{{ route('participants.index') }}" class="link text-sm">&larr; Back to roster</a>

        <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-4">
                <x-ui.avatar :name="$participant->name" size="lg" />

                <div>
                    <h1 class="font-display text-3xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ $participant->name }}</h1>

                    <p class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-600 dark:text-slate-400">
                        <span>{{ $participant->batch?->name ? 'Batch '.$participant->batch->name : 'No batch' }}</span>
                        @if ($participant->call_name)
                            <span>&middot; &ldquo;{{ $participant->call_name }}&rdquo;</span>
                        @endif
                        @if ($participant->email)
                            <span>&middot; {{ $participant->email }}</span>
                        @endif
                        @if ($participant->phone)
                            <span>&middot; {{ $participant->phone }}</span>
                        @endif
                        @if ($participant->nic)
                            <span>&middot; NIC {{ $participant->nic }}</span>
                        @endif
                    </p>

                    <div class="mt-2.5 flex flex-wrap gap-1.5">
                        <x-ui.badge tone="emerald">Attending</x-ui.badge>
                        @if ($participant->import_key === null)
                            <x-ui.badge tone="sky">Self-registered</x-ui.badge>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-ui.alerts />

    <div data-tabs>
        <div class="glass overflow-hidden">
            <div
                role="tablist"
                aria-label="Participant sections"
                class="flex gap-1 overflow-x-auto p-2 sm:p-3"
            >
                @foreach ($tabs as $key => $label)
                    <button
                        type="button"
                        role="tab"
                        id="tab-{{ $key }}"
                        data-tab="{{ $key }}"
                        aria-controls="panel-{{ $key }}"
                        aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}"
                        tabindex="{{ $activeTab === $key ? '0' : '-1' }}"
                        class="tab-button {{ $activeTab === $key ? 'tab-button-active' : '' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <section
            data-panel="participant"
            role="tabpanel"
            id="panel-participant"
            aria-labelledby="tab-participant"
            @if ($activeTab !== 'participant') hidden @endif
            class="mt-6 space-y-6"
        >
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-ui.stat-card label="Ticket price" :value="number_format($summary['ticketPrice'], 2)" tone="violet" hint="Per person × attendees" />
                <x-ui.stat-card label="Total paid" :value="number_format($summary['totalPaid'], 2)" tone="emerald" hint="Across all payments" />
                <x-ui.stat-card label="Remaining" :value="number_format($summary['remaining'], 2)" tone="amber" hint="Still to collect" />
                <x-ui.stat-card label="Refund available" :value="number_format($summary['refund'], 2)" tone="rose" hint="If they cancel" />
            </div>

            <x-ui.glass-card title="Participant details">
                <dl class="grid gap-4 text-sm sm:grid-cols-2 xl:grid-cols-4">
                    <div class="glass-soft p-4">
                        <dt class="label">Name</dt>
                        <dd class="mt-1 font-semibold text-slate-900 dark:text-white">{{ $participant->name }}</dd>
                    </div>
                    <div class="glass-soft p-4">
                        <dt class="label">Call name</dt>
                        <dd class="mt-1 text-slate-800 dark:text-slate-100">{{ $participant->call_name ?? '—' }}</dd>
                    </div>
                    <div class="glass-soft p-4">
                        <dt class="label">Email</dt>
                        <dd class="mt-1 break-all text-slate-800 dark:text-slate-100">{{ $participant->email ?? '—' }}</dd>
                    </div>
                    <div class="glass-soft p-4">
                        <dt class="label">Phone</dt>
                        <dd class="mt-1 text-slate-800 dark:text-slate-100">{{ $participant->phone ?? '—' }}</dd>
                    </div>
                    <div class="glass-soft p-4">
                        <dt class="label">NIC</dt>
                        <dd class="mt-1 text-slate-800 dark:text-slate-100">{{ $participant->nic ?? '—' }}</dd>
                    </div>
                    <div class="glass-soft p-4">
                        <dt class="label">Batch</dt>
                        <dd class="mt-1 text-slate-800 dark:text-slate-100">{{ $participant->batch?->name ?? '—' }}</dd>
                    </div>
                    <div class="glass-soft p-4">
                        <dt class="label">Spouse attending</dt>
                        <dd class="mt-1 text-slate-800 dark:text-slate-100">
                            @if ($participant->spouse_attending === null)
                                —
                            @elseif ($participant->spouse_attending)
                                Yes{{ $participant->spouse_name ? ' · '.$participant->spouse_name : '' }}
                            @else
                                No
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-ui.glass-card>
        </section>

        <section
            data-panel="payment"
            role="tabpanel"
            id="panel-payment"
            aria-labelledby="tab-payment"
            @if ($activeTab !== 'payment') hidden @endif
            class="mt-6 space-y-6"
        >
            @if ($canManage)
                <x-ui.glass-card title="Record a payment">
                    <form method="POST" action="{{ route('participants.payments.store', $participant) }}" enctype="multipart/form-data" class="space-y-3.5">
                        @csrf

                        <div>
                            <label for="amount" class="label">Amount</label>
                            <input id="amount" name="amount" type="number" step="0.01" min="0.01" required placeholder="0.00" class="field mt-1.5">
                        </div>

                        <div>
                            <label for="method" class="label">Method</label>
                            <select id="method" name="method" required class="field mt-1.5">
                                @foreach (\App\Participation\Entities\PaymentMethod::cases() as $method)
                                    <option value="{{ $method->value }}">{{ $method->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="paid_at" class="label">Paid at</label>
                            <input id="paid_at" name="paid_at" type="datetime-local" required class="field mt-1.5">
                        </div>

                        <div>
                            <label for="slip" class="label">Payment slip (optional)</label>
                            <input id="slip" name="slip" type="file" accept=".pdf,.jpg,.jpeg,.png" class="field mt-1.5">
                        </div>

                        <div>
                            <label for="note" class="label">Note</label>
                            <textarea id="note" name="note" rows="2" maxlength="1000" class="field mt-1.5"></textarea>
                        </div>

                        <x-ui.button type="submit" class="w-full">Record payment</x-ui.button>
                    </form>
                </x-ui.glass-card>
            @endif

            <section class="glass overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-300/40 p-4 dark:border-white/10 sm:p-5">
                    <h2 class="font-display text-lg font-semibold tracking-tight text-slate-900 dark:text-white">Payment history</h2>
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ $participant->payments->count() }} {{ \Illuminate\Support\Str::plural('payment', $participant->payments->count()) }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-300/40 text-left text-sm dark:divide-white/10">
                        <thead class="table-head">
                            <tr>
                                <th scope="col" class="px-4 py-3">Date</th>
                                <th scope="col" class="px-4 py-3">Amount</th>
                                <th scope="col" class="px-4 py-3">Method</th>
                                <th scope="col" class="px-4 py-3">Slip</th>
                                <th scope="col" class="hidden px-4 py-3 md:table-cell">Note</th>
                                <th scope="col" class="hidden px-4 py-3 md:table-cell">Recorded by</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-300/30 dark:divide-white/5">
                            @forelse ($participant->payments as $payment)
                                <tr class="table-row">
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $payment->paid_at->format('j M Y, H:i') }}</td>
                                    <td class="px-4 py-3 font-display font-semibold tabular-nums text-slate-900 dark:text-white">{{ number_format($payment->amount, 2) }}</td>
                                    <td class="px-4 py-3"><x-ui.badge tone="neutral">{{ $payment->method->label() }}</x-ui.badge></td>
                                    <td class="px-4 py-3">
                                        @if ($payment->slip_path)
                                            <a href="{{ route('participants.payments.slip', [$participant, $payment]) }}" class="link inline-flex items-center gap-1 text-xs">
                                                View slip
                                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M7 17 17 7M9 7h8v8" />
                                                </svg>
                                            </a>
                                        @else
                                            <span class="text-slate-400 dark:text-slate-500">—</span>
                                        @endif
                                    </td>
                                    <td class="hidden max-w-xs px-4 py-3 text-slate-600 dark:text-slate-300 md:table-cell">{{ $payment->note ?? '—' }}</td>
                                    <td class="hidden px-4 py-3 text-slate-600 dark:text-slate-300 md:table-cell">{{ $payment->recordedBy?->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-12 text-center">
                                        <p class="font-display font-semibold text-slate-700 dark:text-slate-200">No payments recorded yet.</p>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Record the first payment to start the history.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </section>

        <section
            data-panel="ticket"
            role="tabpanel"
            id="panel-ticket"
            aria-labelledby="tab-ticket"
            @if ($activeTab !== 'ticket') hidden @endif
            class="mt-6 space-y-6"
        >
            @if ($canManage)
                <x-ui.glass-card title="Issue a ticket">
                    <p class="mb-4 text-sm text-slate-600 dark:text-slate-400">
                        Issue a ticket once the payment is settled. The ticket number is generated automatically.
                    </p>

                    <form method="POST" action="{{ route('participants.tickets.store', $participant) }}">
                        @csrf
                        <x-ui.button type="submit" class="w-full">Issue ticket</x-ui.button>
                    </form>
                </x-ui.glass-card>
            @endif

            <section class="glass overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-300/40 p-4 dark:border-white/10 sm:p-5">
                    <h2 class="font-display text-lg font-semibold tracking-tight text-slate-900 dark:text-white">Issued tickets</h2>
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ $participant->issuedTickets->count() }} {{ \Illuminate\Support\Str::plural('ticket', $participant->issuedTickets->count()) }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-300/40 text-left text-sm dark:divide-white/10">
                        <thead class="table-head">
                            <tr>
                                <th scope="col" class="px-4 py-3">Ticket no.</th>
                                <th scope="col" class="px-4 py-3">Issued on</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                                <th scope="col" class="hidden px-4 py-3 md:table-cell">Issued by</th>
                                <th scope="col" class="px-4 py-3">Download</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-300/30 dark:divide-white/5">
                            @forelse ($participant->issuedTickets as $ticket)
                                <tr class="table-row">
                                    <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-900 dark:text-white">{{ $ticket->ticket_number }}</td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $ticket->issued_at->format('j M Y, H:i') }}</td>
                                    <td class="px-4 py-3">
                                        <x-ui.badge :tone="$ticket->status === \App\Participation\Entities\TicketStatus::Issued ? 'emerald' : 'rose'">
                                            {{ $ticket->status->label() }}
                                        </x-ui.badge>
                                    </td>
                                    <td class="hidden px-4 py-3 text-slate-600 dark:text-slate-300 md:table-cell">{{ $ticket->issuedBy?->name ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        @if ($ticket->file_path)
                                            <a href="{{ route('participants.tickets.download', [$participant, $ticket]) }}" class="link text-xs">Download</a>
                                        @else
                                            <span class="text-slate-400 dark:text-slate-500">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-12 text-center">
                                        <p class="font-display font-semibold text-slate-700 dark:text-slate-200">No tickets issued yet.</p>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Issue a ticket once the payment is settled.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </section>
    </div>
</x-layouts.app>
