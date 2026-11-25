<x-layouts.app title="{{ $participant->name }}{{ $participant->call_name ? ' ('.$participant->call_name.')' : '' }}">
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
                    <h1 class="font-display text-3xl font-semibold tracking-tight text-slate-900 dark:text-white">
                        {{ $participant->name }}@if ($participant->call_name) <span class="font-normal text-slate-500 dark:text-slate-400">({{ $participant->call_name }})</span>@endif
                    </h1>
                </div>
            </div>
        </div>

        <div class="glass mt-5 overflow-x-auto p-3 sm:p-4">
            <x-ui.progress-stepper :status="$progressStatus" />
        </div>
    </div>


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
            <x-ui.glass-card title="Attendance" subtitle="Who is coming to the gathering?">
                <x-slot:actions>
                    <x-ui.button
                        type="button"
                        variant="secondary"
                        size="sm"
                        data-history-open="attendance-history"
                        aria-haspopup="dialog"
                        aria-controls="attendance-history"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 12a9 9 0 1 0 3-6.7L3 8" />
                            <path d="M3 3v5h5M12 7v5l3 2" />
                        </svg>
                        History
                    </x-ui.button>
                </x-slot:actions>

                @if ($canManage)
                    <div class="overflow-hidden rounded-xl border border-slate-300/50 dark:border-white/10">
                        <div class="divide-y divide-slate-300/40 dark:divide-white/10">
                            <div class="flex items-center justify-between gap-4 px-4 py-3.5">
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-900 dark:text-white">
                                        {{ $participant->name }}@if ($participant->call_name) <span class="font-normal text-slate-500 dark:text-slate-400">({{ $participant->call_name }})</span>@endif
                                    </p>
                                </div>

                                <div class="flex shrink-0 items-center gap-2.5">
                                    <span class="text-xs font-medium {{ $participant->attending ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-500 dark:text-slate-400' }}">
                                        {{ $participant->attending ? 'Attending' : 'Not attending' }}
                                    </span>

                                    <form method="POST" action="{{ route('participants.attendance.update', $participant) }}">
                                        @csrf
                                        <input type="hidden" name="attending" value="0">
                                        <label class="relative inline-flex h-6 w-11 cursor-pointer items-center">
                                            <input
                                                type="checkbox"
                                                name="attending"
                                                value="1"
                                                @checked($participant->attending)
                                                data-attendance-toggle
                                                aria-label="Attendance for {{ $participant->name }}"
                                                class="peer sr-only"
                                            >
                                            <span class="absolute inset-0 rounded-full bg-slate-300/80 transition peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-violet-500 dark:bg-white/15 dark:peer-checked:bg-emerald-500"></span>
                                            <span class="absolute left-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></span>
                                        </label>
                                    </form>
                                </div>
                            </div>

                            @foreach ($participant->relatedParticipants as $relation)
                                <div class="flex items-center justify-between gap-4 px-4 py-3.5">
                                    <div class="min-w-0">
                                        <p class="flex flex-wrap items-center gap-2 font-semibold text-slate-900 dark:text-white">
                                            {{ $relation->name }}
                                            <x-ui.badge tone="violet">({{ $relation->relation?->label() }})</x-ui.badge>
                                        </p>
                                    </div>

                                    <div class="flex shrink-0 items-center gap-2.5">
                                        <span class="text-xs font-medium {{ $relation->attending ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-500 dark:text-slate-400' }}">
                                            {{ $relation->attending ? 'Attending' : 'Not attending' }}
                                        </span>

                                        <form method="POST" action="{{ route('participants.relations.attendance.update', [$participant, $relation]) }}">
                                            @csrf
                                            <input type="hidden" name="attending" value="0">
                                            <label class="relative inline-flex h-6 w-11 cursor-pointer items-center">
                                                <input
                                                    type="checkbox"
                                                    name="attending"
                                                    value="1"
                                                    @checked($relation->attending)
                                                    data-attendance-toggle
                                                    aria-label="Attendance for {{ $relation->name }}"
                                                    class="peer sr-only"
                                                >
                                                <span class="absolute inset-0 rounded-full bg-slate-300/80 transition peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-violet-500 dark:bg-white/15 dark:peer-checked:bg-emerald-500"></span>
                                                <span class="absolute left-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></span>
                                            </label>
                                        </form>

                                        <form method="POST" action="{{ route('participants.relations.destroy', [$participant, $relation]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                aria-label="Remove {{ $relation->name }}"
                                                title="Remove {{ $relation->name }}"
                                                class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-500/10 hover:text-rose-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 dark:hover:text-rose-300"
                                            >
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M18 6 6 18M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <details class="group border-t border-slate-300/40 dark:border-white/10">
                            <summary class="flex cursor-pointer list-none items-center gap-2 px-4 py-3 text-sm font-semibold text-violet-700 transition hover:bg-violet-500/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-violet-500 dark:text-violet-300 [&::-webkit-details-marker]:hidden">
                                <span class="flex h-6 w-6 items-center justify-center rounded-lg border border-violet-400/40 bg-violet-500/10 text-base leading-none">+</span>
                                Add a relation
                            </summary>

                            <form method="POST" action="{{ route('participants.relations.store', $participant) }}" class="grid gap-3 border-t border-slate-300/40 p-4 dark:border-white/10 sm:grid-cols-[1fr_10rem_auto] sm:items-end">
                                @csrf

                                <div>
                                    <label for="relation_name" class="label">Name</label>
                                    <input
                                        id="relation_name"
                                        name="name"
                                        type="text"
                                        maxlength="255"
                                        required
                                        value="{{ old('name') }}"
                                        placeholder="Partner or child's name"
                                        class="field mt-1.5"
                                    >
                                    @error('name')
                                        <p class="mt-1 text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="relation_type" class="label">Relation</label>
                                    <select id="relation_type" name="relation" required class="field mt-1.5">
                                        @foreach (\App\Participation\Entities\ParticipantRelation::cases() as $option)
                                            <option value="{{ $option->value }}" @selected(old('relation') === $option->value)>{{ $option->label() }}</option>
                                        @endforeach
                                    </select>
                                    @error('relation')
                                        <p class="mt-1 text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
                                    @enderror
                                </div>

                                <x-ui.button type="submit" size="sm">Add</x-ui.button>
                            </form>
                        </details>
                    </div>
                @endif

                <div class="mt-5 border-t border-slate-300/40 pt-4 dark:border-white/10">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p class="label">Total ticket price</p>
                            <p class="mt-1 font-display text-2xl font-semibold tabular-nums text-slate-900 dark:text-white">{{ number_format($summary['ticketPrice'], 2) }}</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                @forelse ($priceBreakdown as $line)
                                    {{ $line['label'] }} {{ $line['quantity'] }} &times; {{ $line['unitPrice'] === 0.0 ? 'Free' : number_format($line['unitPrice'], 2) }}{{ $loop->last ? '' : ' · ' }}
                                @empty
                                    No one is attending
                                @endforelse
                            </p>
                        </div>

                        <a
                            href="{{ route('participants.show', [$participant, 'tab' => 'payment']) }}"
                            data-open-tab="payment"
                            class="link inline-flex items-center gap-1 text-sm"
                        >
                            Payment details <span aria-hidden="true">&rarr;</span>
                        </a>
                    </div>
                </div>

                @if ($hasIssuedTickets && (! $participant->attending || $participant->relatedParticipants->contains(fn ($relation): bool => ! $relation->attending)))
                    <p class="mt-4 flex items-start gap-2 rounded-xl border border-amber-400/35 bg-amber-400/15 px-4 py-3 text-sm font-medium text-amber-900 dark:text-amber-100">
                        A ticket has already been issued while someone is not attending. Review the ticket manually.
                    </p>
                @endif

            </x-ui.glass-card>

            <dialog
                id="attendance-history"
                aria-labelledby="attendance-history-title"
                class="m-auto w-[min(40rem,calc(100vw-2rem))] max-h-[85vh] rounded-2xl border border-slate-300/60 bg-white/95 p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/50 backdrop:backdrop-blur-sm dark:border-white/10 dark:bg-slate-900/95 dark:text-white"
            >
                <div class="flex items-start justify-between gap-4 border-b border-slate-300/40 px-5 py-4 dark:border-white/10">
                    <div>
                        <h2 id="attendance-history-title" class="font-display text-lg font-semibold tracking-tight">
                            History
                        </h2>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                            {{ $participant->name }}
                        </p>
                    </div>

                    <button
                        type="button"
                        data-history-close
                        aria-label="Close history"
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-500/10 hover:text-slate-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 dark:hover:text-slate-200"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M18 6 6 18M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="max-h-[60vh] overflow-y-auto px-5 py-1">
                    <div class="divide-y divide-slate-300/40 dark:divide-white/10">
                        @forelse ($participant->attendanceChanges as $change)
                            @php($descriptions = $change->changes())
                            <div class="flex gap-3 py-3.5">
                                <span @class([
                                    'mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full ring-2',
                                    'bg-violet-500 ring-violet-500/20' => $loop->first,
                                    'bg-slate-300 ring-slate-400/20 dark:bg-slate-600 dark:ring-slate-400/10' => ! $loop->first,
                                ])></span>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                                        <p class="font-semibold text-slate-900 dark:text-white">
                                            {{ $descriptions[0] ?? 'Attendance updated' }}
                                        </p>
                                        <time
                                            datetime="{{ $change->created_at->toIso8601String() }}"
                                            class="shrink-0 text-xs tabular-nums text-slate-500 dark:text-slate-400"
                                        >
                                            {{ $change->created_at->format('j M Y, H:i') }}
                                        </time>
                                    </div>

                                    <p class="mt-0.5 text-sm font-medium text-slate-600 dark:text-slate-300">
                                        {{ $change->changedBy?->name ?? 'System' }}
                                    </p>

                                    @if (count($descriptions) > 1)
                                        <ul class="mt-1 space-y-0.5 text-sm text-slate-500 dark:text-slate-400">
                                            @foreach (array_slice($descriptions, 1) as $description)
                                                <li>{{ $description }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                                No attendance changes have been recorded yet.
                            </p>
                        @endforelse
                    </div>
                </div>

                <div class="flex justify-end border-t border-slate-300/40 px-5 py-3 dark:border-white/10">
                    <x-ui.button type="button" variant="secondary" size="sm" data-history-close>Close</x-ui.button>
                </div>
            </dialog>

            <x-ui.glass-card title="More information">
                <dl class="grid gap-4 text-sm sm:grid-cols-2">
                    <div class="glass-soft p-4">
                        <dt class="label">Contact number</dt>
                        <dd class="mt-1 text-slate-800 dark:text-slate-100">{{ $participant->phone ?? '—' }}</dd>
                    </div>
                    <div class="glass-soft p-4">
                        <dt class="label">Batch</dt>
                        <dd class="mt-1 text-slate-800 dark:text-slate-100">{{ $participant->batch?->name ?? '—' }}</dd>
                    </div>
                    <div class="glass-soft p-4">
                        <dt class="label">Email</dt>
                        <dd class="mt-1 break-all text-slate-800 dark:text-slate-100">{{ $participant->email ?? '—' }}</dd>
                    </div>
                    <div class="glass-soft p-4">
                        <dt class="label">NIC</dt>
                        <dd class="mt-1 text-slate-800 dark:text-slate-100">{{ $participant->nic ?? '—' }}</dd>
                    </div>
                </dl>
            </x-ui.glass-card>

            @if ($canCreateUserAccount)
                <x-ui.glass-card title="Account access" subtitle="Give this participant a login account.">
                    @if ($linkedUser)
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300">User account exists</p>
                                <p class="mt-1 break-all text-sm text-slate-600 dark:text-slate-400">{{ $linkedUser->email }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <x-ui.role-badge :role="$linkedUser->role" />
                                    <x-ui.badge tone="violet">{{ $linkedUser->batch?->name ?? 'No batch' }}</x-ui.badge>
                                </div>
                            </div>
                            <a href="{{ route('users.index', ['search' => $linkedUser->email]) }}" class="link text-sm">View in Users</a>
                        </div>
                    @elseif ($participant->isArchived())
                        <p class="text-sm text-slate-600 dark:text-slate-400">Archived participants cannot be given a user account.</p>
                    @else
                        <form method="POST" action="{{ route('participants.account.store', $participant) }}" class="space-y-3.5">
                            @csrf

                            <div>
                                <label for="account-email" class="label">Email address</label>
                                <input
                                    id="account-email"
                                    name="email"
                                    type="email"
                                    maxlength="255"
                                    value="{{ old('email', $participant->email) }}"
                                    placeholder="name@example.com"
                                    required
                                    class="field mt-1.5"
                                >
                                @error('email')
                                    <p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>
                                @enderror
                                @error('participant')
                                    <p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>
                                @enderror
                            </div>

                            <x-ui.button type="submit">Create user account</x-ui.button>
                            <p class="text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                                The participant can sign in with this email and a one-time code. No email is sent now.
                            </p>
                        </form>
                    @endif
                </x-ui.glass-card>
            @endif
        </section>

        <section
            data-panel="payment"
            role="tabpanel"
            id="panel-payment"
            aria-labelledby="tab-payment"
            @if ($activeTab !== 'payment') hidden @endif
            class="mt-6 space-y-6"
        >
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-ui.stat-card label="Ticket price" :value="number_format($summary['ticketPrice'], 2)" tone="violet" hint="Participant + relations" />
                <x-ui.stat-card label="Total paid" :value="number_format($summary['totalPaid'], 2)" tone="emerald" hint="Across all payments" />
                <x-ui.stat-card label="Remaining" :value="number_format($summary['remaining'], 2)" tone="amber" hint="Still to collect" />
                <x-ui.stat-card label="Refund available" :value="number_format($summary['refund'], 2)" tone="rose" hint="If they cancel" />
            </div>

            @if ($canManage)
                <x-ui.glass-card title="Record a payment">
                    <form method="POST" action="{{ route('participants.payments.store', $participant) }}" enctype="multipart/form-data" class="space-y-3.5">
                        @csrf

                        <div>
                            <label for="amount" class="label">Amount</label>
                            <input id="amount" name="amount" type="number" step="0.01" min="0.01" required placeholder="0.00" value="{{ old('amount') }}" class="field mt-1.5">
                        </div>

                        <div>
                            <label for="method" class="label">Method</label>
                            <select id="method" name="method" required class="field mt-1.5">
                                @foreach (\App\Participation\Entities\PaymentMethod::cases() as $method)
                                    <option value="{{ $method->value }}" @selected(old('method') === $method->value)>{{ $method->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="paid_at" class="label">Paid on</label>
                            <input id="paid_at" name="paid_at" type="date" required value="{{ old('paid_at', now()->toDateString()) }}" class="field mt-1.5">
                        </div>

                        <div>
                            <label for="slip" class="label">Payment slip (optional)</label>
                            <input
                                id="slip"
                                name="slip"
                                type="file"
                                accept="{{ \App\Participation\Entities\Payment::SLIP_ACCEPT }}"
                                data-file-summary="slip-file-summary"
                                data-max-bytes="{{ 5 * 1024 * 1024 }}"
                                class="field mt-1.5"
                            >
                            <p id="slip-file-summary" hidden class="mt-1.5 text-xs leading-relaxed text-slate-500 dark:text-slate-400" role="status" aria-live="polite"></p>
                            <p class="mt-1.5 text-xs leading-relaxed text-slate-500 dark:text-slate-400">JPG, PNG, HEIC, HEIF, or PDF up to 5 MB.</p>
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
                                <th scope="col" class="px-4 py-3">Added by</th>
                                <th scope="col" class="px-4 py-3">Slip</th>
                                <th scope="col" class="px-4 py-3"><span class="sr-only">Details</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-300/30 dark:divide-white/5">
                            @forelse ($participant->payments as $payment)
                                @php($slipKind = $payment->slipPreviewKind())
                                <tr
                                    class="table-row cursor-pointer"
                                    tabindex="0"
                                    role="button"
                                    aria-label="Edit payment of {{ number_format($payment->amount, 2) }} on {{ $payment->paid_at->format('j M Y') }}"
                                    data-payment-open
                                    data-payment-open-error="{{ old('payment_id') == $payment->id ? '1' : '0' }}"
                                    data-payment-error-tab="{{ old('payment_id') == $payment->id && $errors->has('slip') ? 'slip' : 'details' }}"
                                    data-payment-id="{{ $payment->id }}"
                                    data-payment-date="{{ $payment->paid_at->format('j M Y') }}"
                                    data-payment-raw-date="{{ $payment->paid_at->toDateString() }}"
                                    data-payment-amount="{{ number_format($payment->amount, 2) }}"
                                    data-payment-raw-amount="{{ $payment->amount }}"
                                    data-payment-method="{{ $payment->method->label() }}"
                                    data-payment-method-value="{{ $payment->method->value }}"
                                    data-payment-added-by="{{ $payment->addedBy() }}"
                                    data-payment-updated-by="{{ $payment->lastUpdatedBy() ?? '—' }}"
                                    data-payment-has-slip="{{ $payment->slip_path !== null ? '1' : '0' }}"
                                    data-payment-slip-kind="{{ $slipKind ?? 'pdf' }}"
                                    data-payment-slip-name="{{ $payment->slipDisplayName() ?? '' }}"
                                    data-payment-slip-url="{{ route('participants.payments.slip', [$participant, $payment]) }}"
                                    data-payment-slip-pdf-url="{{ route('participants.payments.slip.preview', [$participant, $payment]) }}"
                                    data-payment-download-url="{{ route('participants.payments.slip', [$participant, $payment, 'download' => 1]) }}"
                                    data-payment-old-amount="{{ old('payment_id') == $payment->id ? old('amount') : '' }}"
                                    data-payment-old-method="{{ old('payment_id') == $payment->id ? old('method') : '' }}"
                                    data-payment-old-date="{{ old('payment_id') == $payment->id ? old('paid_at') : '' }}"
                                    data-payment-old-remove-slip="{{ old('payment_id') == $payment->id && old('remove_slip') ? '1' : '0' }}"
                                >
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $payment->paid_at->format('j M Y') }}</td>
                                    <td class="px-4 py-3 font-display font-semibold tabular-nums text-slate-900 dark:text-white">{{ number_format($payment->amount, 2) }}</td>
                                    <td class="px-4 py-3"><x-ui.badge tone="neutral">{{ $payment->method->label() }}</x-ui.badge></td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $payment->addedBy() }}</td>
                                    <td class="px-4 py-3">
                                        @if ($payment->slip_path)
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-violet-700 dark:text-violet-300">
                                                Available
                                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M9 12l2 2 4-4" />
                                                </svg>
                                            </span>
                                        @else
                                            <span class="text-slate-400 dark:text-slate-500">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-400 dark:text-slate-500">
                                        <div class="flex items-center justify-end gap-1.5">
                                            @if ($canManage)
                                                <button
                                                    type="button"
                                                    data-payment-delete
                                                    aria-label="Delete payment of {{ number_format($payment->amount, 2) }} on {{ $payment->paid_at->format('j M Y') }}"
                                                    title="Delete payment"
                                                    class="flex h-7 w-7 items-center justify-center rounded-lg transition hover:bg-rose-500/10 hover:text-rose-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 dark:hover:text-rose-300"
                                                >
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v5M14 11v5" />
                                                    </svg>
                                                </button>
                                            @endif

                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="m9 18 6-6-6-6" />
                                            </svg>
                                        </div>
                                    </td>
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

            @if ($canManage)
                <form id="payment-edit-form" data-payment-edit method="POST" enctype="multipart/form-data" class="hidden">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="payment_id" value="">
                    <input type="hidden" name="remove_slip" value="0" data-payment-remove-slip>
                </form>

                <form id="payment-delete-form" method="POST" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            @endif

            <dialog
                id="payment-detail"
                aria-labelledby="payment-detail-title"
                data-update-route-template="{{ route('participants.payments.update', [$participant, '__PAYMENT__']) }}"
                class="m-auto w-[min(40rem,calc(100vw-2rem))] max-h-[88vh] rounded-2xl border border-slate-300/60 bg-white/95 p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/50 backdrop:backdrop-blur-sm dark:border-white/10 dark:bg-slate-900/95 dark:text-white"
            >
                <div class="flex max-h-[88vh] flex-col">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-300/40 px-5 py-4 dark:border-white/10">
                        <div>
                            <h2 id="payment-detail-title" class="font-display text-lg font-semibold tracking-tight">{{ $canManage ? 'Edit payment' : 'Payment details' }}</h2>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400" data-payment-summary>—</p>
                        </div>

                        <button
                            type="button"
                            data-payment-close
                            aria-label="Close payment details"
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-500/10 hover:text-slate-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 dark:hover:text-slate-200"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M18 6 6 18M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto px-5 py-4">
                        @if ($errors->any())
                            <div class="mb-4 rounded-xl border border-rose-400/30 bg-rose-500/10 px-3.5 py-3 text-sm text-rose-800 dark:text-rose-200" role="alert">
                                <p class="font-semibold">Could not save payment</p>
                                <ul class="mt-1 list-inside list-disc space-y-0.5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div role="tablist" aria-label="Payment sections" class="flex gap-1 rounded-xl bg-slate-100/70 p-1 dark:bg-white/5">
                            <button
                                type="button"
                                role="tab"
                                id="payment-form-tab-details"
                                data-payment-form-tab="details"
                                aria-controls="payment-form-panel-details"
                                aria-selected="true"
                                class="tab-button flex-1"
                            >
                                Payment details
                            </button>
                            <button
                                type="button"
                                role="tab"
                                id="payment-form-tab-slip"
                                data-payment-form-tab="slip"
                                aria-controls="payment-form-panel-slip"
                                aria-selected="false"
                                class="tab-button flex-1"
                            >
                                Payment slip
                            </button>
                        </div>

                        <div
                            id="payment-form-panel-details"
                            data-payment-form-panel="details"
                            role="tabpanel"
                            aria-labelledby="payment-form-tab-details"
                            class="mt-4 space-y-3.5"
                        >
                            @if ($canManage)
                                <div class="grid gap-3.5 sm:grid-cols-2">
                                    <div>
                                        <label for="edit-amount" class="label">Amount</label>
                                        <input id="edit-amount" name="amount" form="payment-edit-form" type="number" step="0.01" min="0.01" required placeholder="0.00" class="field mt-1.5">
                                    </div>

                                    <div>
                                        <label for="edit-method" class="label">Method</label>
                                        <select id="edit-method" name="method" form="payment-edit-form" required class="field mt-1.5">
                                            @foreach (\App\Participation\Entities\PaymentMethod::cases() as $method)
                                                <option value="{{ $method->value }}">{{ $method->label() }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label for="edit-paid-at" class="label">Paid on</label>
                                    <input id="edit-paid-at" name="paid_at" form="payment-edit-form" type="date" required class="field mt-1.5">
                                </div>
                            @else
                                <dl class="grid gap-3 sm:grid-cols-3">
                                    <div class="glass-soft p-3">
                                        <dt class="label">Paid on</dt>
                                        <dd class="mt-1 text-sm font-medium text-slate-800 dark:text-slate-100" data-payment-read-date>—</dd>
                                    </div>
                                    <div class="glass-soft p-3">
                                        <dt class="label">Amount</dt>
                                        <dd class="mt-1 font-display text-sm font-semibold tabular-nums text-slate-900 dark:text-white" data-payment-read-amount>—</dd>
                                    </div>
                                    <div class="glass-soft p-3">
                                        <dt class="label">Method</dt>
                                        <dd class="mt-1 text-sm font-medium text-slate-800 dark:text-slate-100" data-payment-read-method>—</dd>
                                    </div>
                                </dl>
                            @endif

                            <dl class="grid gap-3 sm:grid-cols-2">
                                <div class="glass-soft p-3">
                                    <dt class="label">Added by</dt>
                                    <dd class="mt-1 text-sm font-medium text-slate-800 dark:text-slate-100" data-payment-field-added-by>—</dd>
                                </div>
                                <div class="glass-soft p-3">
                                    <dt class="label">Last updated by</dt>
                                    <dd class="mt-1 text-sm font-medium text-slate-800 dark:text-slate-100" data-payment-field-updated-by>—</dd>
                                </div>
                            </dl>
                        </div>

                        <div
                            id="payment-form-panel-slip"
                            data-payment-form-panel="slip"
                            role="tabpanel"
                            aria-labelledby="payment-form-tab-slip"
                            hidden
                            class="mt-4 space-y-3"
                        >
                            <div data-payment-slip-preview hidden class="overflow-hidden rounded-xl border border-slate-300/50 bg-slate-100/60 dark:border-white/10 dark:bg-black/20">
                                <img data-payment-slip-image hidden alt="Payment slip" class="mx-auto max-h-[24rem] w-auto object-contain">
                                <div data-payment-slip-pdf hidden class="h-[24rem] w-full overflow-y-auto bg-slate-200/50 p-3 dark:bg-black/30">
                                    <p data-payment-slip-pdf-status class="px-2 py-6 text-center text-sm text-slate-500 dark:text-slate-400">Loading PDF…</p>
                                    <div data-payment-slip-pdf-pages class="mx-auto space-y-3"></div>
                                </div>
                            </div>

                            <div data-payment-slip-empty class="rounded-xl border border-dashed border-slate-300/60 px-4 py-6 text-center dark:border-white/10">
                                <p class="font-display font-semibold text-slate-700 dark:text-slate-200" data-payment-slip-empty-title>No slip uploaded for this payment.</p>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400" data-payment-slip-empty-hint>{{ $canManage ? 'Choose a JPG, PNG, HEIC, HEIF, or PDF file below.' : 'No slip was attached to this payment.' }}</p>
                            </div>

                            <div data-payment-slip-meta hidden class="flex flex-wrap items-center justify-between gap-2">
                                <p class="truncate text-sm font-medium text-slate-700 dark:text-slate-200" data-payment-slip-name>Payment slip</p>
                                <div class="flex flex-wrap items-center gap-3">
                                    <a href="#" target="_blank" rel="noopener" data-payment-open-original class="link text-xs">Open original</a>
                                    <a href="#" data-payment-download class="link text-xs">Download</a>
                                </div>
                            </div>

                            @if ($canManage)
                                <div data-payment-slip-actions class="flex flex-wrap gap-2">
                                    <x-ui.button type="button" variant="secondary" size="sm" data-payment-slip-change>Change slip</x-ui.button>
                                    <x-ui.button type="button" variant="secondary" size="sm" data-payment-slip-remove>Remove slip</x-ui.button>
                                </div>

                                <div data-payment-slip-upload hidden class="space-y-3 rounded-xl border border-slate-300/50 bg-white/45 p-3.5 dark:border-white/10 dark:bg-white/5">
                                    <div>
                                        <label for="edit-slip" class="label" data-payment-slip-label>Choose payment slip</label>
                                        <input id="edit-slip" name="slip" form="payment-edit-form" type="file" accept="{{ \App\Participation\Entities\Payment::SLIP_ACCEPT }}" class="field mt-1.5">
                                    </div>

                                    <p class="text-xs text-slate-500 dark:text-slate-400">JPG, PNG, HEIC, HEIF, or PDF up to 5 MB.</p>
                                    <p class="text-xs font-medium text-amber-700 dark:text-amber-300" data-payment-slip-pending-removal hidden>The current slip will be removed when you save.</p>
                                    <p class="text-xs font-medium text-violet-700 dark:text-violet-300" data-payment-slip-selected hidden></p>
                                    <button type="button" data-payment-slip-keep hidden class="link text-xs">Keep current slip</button>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-300/40 px-5 py-3 dark:border-white/10">
                        @if ($canManage)
                            <x-ui.button type="submit" form="payment-edit-form">Save changes</x-ui.button>
                        @endif
                        <x-ui.button type="button" variant="secondary" size="sm" data-payment-close class="{{ $canManage ? '' : 'ml-auto' }}">Close</x-ui.button>
                    </div>
                </div>
            </dialog>

            @if ($canManage)
                <dialog
                    id="payment-delete-confirm"
                    aria-labelledby="payment-delete-title"
                    aria-describedby="payment-delete-description"
                    data-payment-delete-confirm
                    data-delete-route-template="{{ route('participants.payments.destroy', [$participant, '__PAYMENT__']) }}"
                    class="m-auto w-[min(28rem,calc(100vw-2rem))] max-h-[85vh] rounded-2xl border border-rose-300/60 bg-white/95 p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/50 backdrop:backdrop-blur-sm dark:border-rose-400/20 dark:bg-slate-900/95 dark:text-white"
                >
                    <div class="flex gap-4 p-5">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-300">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v5M14 11v5" />
                            </svg>
                        </span>

                        <div>
                            <h2 id="payment-delete-title" class="font-display text-lg font-semibold tracking-tight">Delete payment?</h2>
                            <p id="payment-delete-description" class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                                This will permanently remove the payment and its uploaded slip. This action cannot be undone.
                            </p>
                            <p data-payment-delete-summary class="mt-3 rounded-lg bg-rose-500/10 px-3 py-2 text-sm font-semibold text-rose-800 dark:text-rose-200">—</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-end gap-2 border-t border-slate-300/40 px-5 py-3 dark:border-white/10">
                        <x-ui.button type="button" variant="secondary" size="sm" data-payment-delete-cancel>Cancel</x-ui.button>
                        <x-ui.button type="submit" form="payment-delete-form" variant="danger" size="sm">Delete payment</x-ui.button>
                    </div>
                </dialog>
            @endif
        </section>

        <section
            data-panel="ticket"
            role="tabpanel"
            id="panel-ticket"
            aria-labelledby="tab-ticket"
            @if ($activeTab !== 'ticket') hidden @endif
            class="mt-6 space-y-6"
        >
            <x-ui.glass-card title="Issue a ticket" subtitle="Generate a PDF, email it, and keep a copy for resending.">
                @if ($ticketEligibility['canGenerate'])
                    <p class="rounded-xl border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-800 dark:text-emerald-200">
                        Payment is settled. A ticket can be generated for {{ number_format($summary['ticketPrice'], 2) }}.
                    </p>
                @else
                    <p class="rounded-xl border border-amber-400/35 bg-amber-400/15 px-4 py-3 text-sm font-medium text-amber-900 dark:text-amber-100">
                        {{ $ticketEligibility['message'] }}
                    </p>
                @endif

                @if ($canManage)
                    <form method="POST" action="{{ route('participants.tickets.store', $participant) }}" class="mt-4 grid gap-3.5 sm:grid-cols-[1fr_auto] sm:items-end">
                        @csrf

                        <div>
                            <label for="ticket-email" class="label">Email the ticket to (optional)</label>
                            <input
                                id="ticket-email"
                                name="email"
                                type="email"
                                maxlength="255"
                                value="{{ old('email', $participant->email) }}"
                                placeholder="name@example.com"
                                class="field mt-1.5"
                            >
                            @error('email')
                                <p class="mt-1 text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <x-ui.button type="submit" class="w-full sm:w-auto" :disabled="! $ticketEligibility['canGenerate']">Generate and email ticket</x-ui.button>
                    </form>
                @endif
            </x-ui.glass-card>

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
                                <th scope="col" class="hidden px-4 py-3 lg:table-cell">Delivered to</th>
                                <th scope="col" class="hidden px-4 py-3 md:table-cell">Issued by</th>
                                <th scope="col" class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-300/30 dark:divide-white/5">
                            @forelse ($participant->issuedTickets as $ticket)
                                <tr class="table-row">
                                    <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-900 dark:text-white">{{ $ticket->ticket_number }}</td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $ticket->issued_at->format('j M Y, H:i') }}</td>
                                    <td class="px-4 py-3">
                                        <x-ui.badge :tone="$ticket->isActive() ? 'emerald' : 'rose'">
                                            {{ $ticket->status->label() }}
                                        </x-ui.badge>
                                    </td>
                                    <td class="hidden px-4 py-3 lg:table-cell">
                                        @if ($ticket->emailed_at)
                                            <p class="max-w-52 truncate font-medium text-slate-800 dark:text-slate-100">{{ $ticket->emailed_to }}</p>
                                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                                {{ $ticket->emailed_at->format('j M Y, H:i') }}
                                                @if ($ticket->email_send_count > 1)
                                                    · {{ $ticket->email_send_count }} sends
                                                @endif
                                            </p>
                                        @else
                                            <span class="text-slate-400 dark:text-slate-500">Not emailed</span>
                                        @endif
                                    </td>
                                    <td class="hidden px-4 py-3 text-slate-600 dark:text-slate-300 md:table-cell">{{ $ticket->issuedBy?->name ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap items-center justify-end gap-3">
                                            @if ($ticket->file_path)
                                                <a href="{{ route('participants.tickets.download', [$participant, $ticket]) }}" class="link text-xs">Download</a>
                                            @endif

                                            @if ($canManage && $ticket->isActive())
                                                <button
                                                    type="button"
                                                    data-ticket-resend
                                                    data-ticket-number="{{ $ticket->ticket_number }}"
                                                    data-ticket-email="{{ $ticket->emailed_to ?? $participant->email ?? '' }}"
                                                    class="link text-xs"
                                                >
                                                    Resend
                                                </button>
                                                <button
                                                    type="button"
                                                    data-ticket-deactivate
                                                    data-ticket-number="{{ $ticket->ticket_number }}"
                                                    class="text-xs font-medium text-rose-600 transition hover:text-rose-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 dark:text-rose-300 dark:hover:text-rose-200"
                                                >
                                                    Deactivate
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-12 text-center">
                                        <p class="font-display font-semibold text-slate-700 dark:text-slate-200">No tickets issued yet.</p>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Issue a ticket once the payment is settled.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            @if ($canManage)
                <form id="ticket-deactivate-form" method="POST" class="hidden">
                    @csrf
                    @method('PATCH')
                </form>

                <dialog
                    id="ticket-resend-confirm"
                    aria-labelledby="ticket-resend-title"
                    data-ticket-resend-confirm
                    data-resend-route-template="{{ route('participants.tickets.resend', [$participant, '__TICKET__']) }}"
                    class="m-auto w-[min(28rem,calc(100vw-2rem))] max-h-[85vh] rounded-2xl border border-slate-300/60 bg-white/95 p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/50 backdrop:backdrop-blur-sm dark:border-white/10 dark:bg-slate-900/95 dark:text-white"
                >
                    <form method="POST" class="p-5" id="ticket-resend-dialog-form">
                        @csrf
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 id="ticket-resend-title" class="font-display text-lg font-semibold tracking-tight">Resend ticket</h2>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400" data-ticket-resend-summary>—</p>
                            </div>

                            <button
                                type="button"
                                data-ticket-resend-cancel
                                aria-label="Close resend dialog"
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-500/10 hover:text-slate-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 dark:hover:text-slate-200"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M18 6 6 18M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="mt-4">
                            <label for="ticket-resend-email" class="label">Recipient email</label>
                            <input
                                id="ticket-resend-email"
                                name="email"
                                type="email"
                                maxlength="255"
                                required
                                class="field mt-1.5"
                            >
                        </div>

                        <div class="mt-5 flex flex-wrap justify-end gap-2 border-t border-slate-300/40 pt-3 dark:border-white/10">
                            <x-ui.button type="button" variant="secondary" size="sm" data-ticket-resend-cancel>Cancel</x-ui.button>
                            <x-ui.button type="submit" size="sm">Send ticket</x-ui.button>
                        </div>
                    </form>
                </dialog>

                <dialog
                    id="ticket-deactivate-confirm"
                    aria-labelledby="ticket-deactivate-title"
                    aria-describedby="ticket-deactivate-description"
                    data-ticket-deactivate-confirm
                    data-deactivate-route-template="{{ route('participants.tickets.deactivate', [$participant, '__TICKET__']) }}"
                    class="m-auto w-[min(28rem,calc(100vw-2rem))] max-h-[85vh] rounded-2xl border border-rose-300/60 bg-white/95 p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/50 backdrop:backdrop-blur-sm dark:border-rose-400/20 dark:bg-slate-900/95 dark:text-white"
                >
                    <div class="flex gap-4 p-5">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-300">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M18 6 6 18M6 6l12 12" />
                            </svg>
                        </span>

                        <div>
                            <h2 id="ticket-deactivate-title" class="font-display text-lg font-semibold tracking-tight">Deactivate ticket?</h2>
                            <p id="ticket-deactivate-description" class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                                The ticket will stop verifying and cannot be resent. Its file and history are kept for audit.
                            </p>
                            <p data-ticket-deactivate-summary class="mt-3 rounded-lg bg-rose-500/10 px-3 py-2 font-mono text-xs font-semibold text-rose-800 dark:text-rose-200">—</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-end gap-2 border-t border-slate-300/40 px-5 py-3 dark:border-white/10">
                        <x-ui.button type="button" variant="secondary" size="sm" data-ticket-deactivate-cancel>Cancel</x-ui.button>
                        <x-ui.button type="submit" form="ticket-deactivate-form" variant="danger" size="sm">Deactivate ticket</x-ui.button>
                    </div>
                </dialog>
            @endif
        </section>
    </div>
</x-layouts.app>
