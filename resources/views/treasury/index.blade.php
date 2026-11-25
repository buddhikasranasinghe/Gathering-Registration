<x-layouts.app title="Treasury">
    <x-ui.page-header
        eyebrow="Collections"
        title="Treasury"
        description="A live view of expected amounts, payments, outstanding balances, and refunds across every batch."
    >
        <x-slot:actions>
            <a href="{{ route('home') }}" class="link text-sm">Back to home</a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card
            label="Expected"
            :value="number_format($totals['expected'], 2)"
            tone="violet"
            hint="Across {{ $totals['participants'] }} active participants"
        />
        <x-ui.stat-card
            label="Collected"
            :value="number_format($totals['collected'], 2)"
            tone="emerald"
            hint="All recorded payments"
        />
        <x-ui.stat-card
            label="Outstanding"
            :value="number_format($totals['remaining'], 2)"
            tone="amber"
            hint="Still to be collected"
        />
        <x-ui.stat-card
            label="Refundable"
            :value="number_format($totals['refundable'], 2)"
            tone="rose"
            hint="Collected above the expected amount"
        />
    </div>

    <x-ui.glass-card
        title="Batch breakdown"
        subtitle="Totals are calculated per participant and then summed for each batch."
        class="mb-6"
    >
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-300/40 text-left text-sm dark:divide-white/10">
                <thead class="table-head">
                    <tr>
                        <th scope="col" class="px-4 py-3">Batch</th>
                        <th scope="col" class="px-4 py-3 text-right">Participants</th>
                        <th scope="col" class="px-4 py-3 text-right">Expected</th>
                        <th scope="col" class="px-4 py-3 text-right">Collected</th>
                        <th scope="col" class="px-4 py-3 text-right">Outstanding</th>
                        <th scope="col" class="px-4 py-3 text-right">Refundable</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-300/30 dark:divide-white/5">
                    @forelse ($batches as $group)
                        <tr class="table-row">
                            <td class="px-4 py-3">
                                <p class="font-display font-semibold text-slate-900 dark:text-white">{{ $group['name'] }}</p>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                    {{ $group['settled'] }} settled &middot; {{ $group['needsAttention'] }} need attention
                                </p>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $group['participants'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format($group['expected'], 2) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format($group['collected'], 2) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format($group['remaining'], 2) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format($group['refundable'], 2) }}</td>
                            <td class="px-4 py-3 text-right">
                                @if ($group['batch'])
                                    <a href="{{ route('treasury.batches.show', $group['batch']) }}" class="link whitespace-nowrap text-xs">
                                        View batch &rarr;
                                    </a>
                                @else
                                    <span class="text-slate-400 dark:text-slate-500">&mdash;</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-14 text-center text-sm text-slate-500 dark:text-slate-400">
                                No batches have been created yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.glass-card>

    <div class="glass overflow-hidden">
        <div role="tablist" aria-label="Treasury participant views" class="flex gap-1 overflow-x-auto p-2 sm:p-3">
            <a
                href="{{ route('treasury.index', ['tab' => 'attention']) }}"
                role="tab"
                id="tab-attention"
                aria-controls="panel-attention"
                aria-selected="{{ $filters['activeTab'] === 'attention' ? 'true' : 'false' }}"
                class="tab-button {{ $filters['activeTab'] === 'attention' ? 'tab-button-active' : '' }}"
            >
                Needs attention ({{ $totals['needsAttention'] }})
            </a>
            <a
                href="{{ route('treasury.index', ['tab' => 'settled']) }}"
                role="tab"
                id="tab-settled"
                aria-controls="panel-settled"
                aria-selected="{{ $filters['activeTab'] === 'settled' ? 'true' : 'false' }}"
                class="tab-button {{ $filters['activeTab'] === 'settled' ? 'tab-button-active' : '' }}"
            >
                Settled participants ({{ $totals['settled'] }})
            </a>
        </div>
    </div>

    @if ($filters['activeTab'] === 'attention')
        <section id="panel-attention" role="tabpanel" aria-labelledby="tab-attention" class="mt-6">
            <div class="glass overflow-hidden">
                <form
                    method="GET"
                    action="{{ route('treasury.index') }}"
                    data-auto-filter
                    class="flex flex-wrap items-end gap-3 border-b border-slate-300/40 p-4 dark:border-white/10 sm:p-5"
                >
                    <input type="hidden" name="tab" value="attention">

                    <div class="min-w-48 flex-1">
                        <label for="attention-search" class="label">Search</label>
                        <input
                            id="attention-search"
                            name="attention_search"
                            type="search"
                            value="{{ $filters['attentionSearch'] }}"
                            placeholder="Name, phone, NIC or email"
                            class="field mt-1.5"
                        >
                    </div>

                    <div>
                        <label for="attention-batch" class="label">Batch</label>
                        <select id="attention-batch" name="attention_batch" class="field mt-1.5">
                            <option value="">All batches</option>
                            @foreach ($batches as $group)
                                <option
                                    value="{{ $group['batchId'] ?? 'unassigned' }}"
                                    @selected($filters['attentionBatch'] === (string) ($group['batchId'] ?? 'unassigned'))
                                >
                                    {{ $group['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <a href="{{ route('treasury.index', ['tab' => 'attention']) }}" class="link pb-2.5 text-sm">Reset</a>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-300/40 text-left text-sm dark:divide-white/10">
                        <thead class="table-head">
                            <tr>
                                <th scope="col" class="px-4 py-3">Participant</th>
                                <th scope="col" class="px-4 py-3">Batch</th>
                                <th scope="col" class="px-4 py-3 text-right">Total ticket price</th>
                                <th scope="col" class="px-4 py-3 text-right">Remaining</th>
                                <th scope="col" class="px-4 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-300/30 dark:divide-white/5">
                            @forelse ($attention as $row)
                                <tr class="table-row">
                                    <td class="px-4 py-3">
                                        <p class="font-display font-semibold text-slate-900 dark:text-white">
                                            <a href="{{ route('participants.show', $row['participant']) }}" class="transition hover:text-violet-700 dark:hover:text-violet-300">{{ $row['name'] }}</a>
                                        </p>
                                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                            {{ $row['priceSource'] === 'issued_ticket' ? 'Active ticket price' : 'Current ticket pricing' }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3">{{ $row['batchName'] }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums">{{ number_format($row['expected'], 2) }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums">
                                        @if ($row['remaining'] > 0)
                                            {{ number_format($row['remaining'], 2) }}
                                        @else
                                            &mdash;
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('participants.show', $row['participant']) }}" class="link whitespace-nowrap text-xs">
                                            View details &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-14 text-center text-sm text-slate-500 dark:text-slate-400">
                                        No participants match the current filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($attention->hasPages())
                    <div class="border-t border-slate-300/40 p-4 dark:border-white/10">
                        {{ $attention->links() }}
                    </div>
                @endif
            </div>
        </section>
    @else
        <section id="panel-settled" role="tabpanel" aria-labelledby="tab-settled" class="mt-6">
            <div class="glass overflow-hidden">
                <form
                    method="GET"
                    action="{{ route('treasury.index') }}"
                    data-auto-filter
                    class="flex flex-wrap items-end gap-3 border-b border-slate-300/40 p-4 dark:border-white/10 sm:p-5"
                >
                    <input type="hidden" name="tab" value="settled">

                    <div class="min-w-48 flex-1">
                        <label for="settled-search" class="label">Search</label>
                        <input
                            id="settled-search"
                            name="settled_search"
                            type="search"
                            value="{{ $filters['settledSearch'] }}"
                            placeholder="Name, phone, NIC or email"
                            class="field mt-1.5"
                        >
                    </div>

                    <a href="{{ route('treasury.index', ['tab' => 'settled']) }}" class="link pb-2.5 text-sm">Reset</a>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-300/40 text-left text-sm dark:divide-white/10">
                        <thead class="table-head">
                            <tr>
                                <th scope="col" class="px-4 py-3">Participant</th>
                                <th scope="col" class="px-4 py-3">Batch</th>
                                <th scope="col" class="px-4 py-3 text-right">Expected</th>
                                <th scope="col" class="px-4 py-3 text-right">Collected</th>
                                <th scope="col" class="px-4 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-300/30 dark:divide-white/5">
                            @forelse ($settled as $row)
                                <tr class="table-row">
                                    <td class="px-4 py-3">
                                        <p class="font-display font-semibold text-slate-900 dark:text-white">
                                            <a href="{{ route('participants.show', $row['participant']) }}" class="transition hover:text-violet-700 dark:hover:text-violet-300">{{ $row['name'] }}</a>
                                        </p>
                                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                            {{ $row['priceSource'] === 'issued_ticket' ? 'Active ticket price' : 'Current ticket pricing' }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3">{{ $row['batchName'] }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums">{{ number_format($row['expected'], 2) }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums">{{ number_format($row['collected'], 2) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('participants.show', $row['participant']) }}" class="link whitespace-nowrap text-xs">
                                            View details &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-14 text-center text-sm text-slate-500 dark:text-slate-400">
                                        No settled participants match the current search.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($settled->hasPages())
                    <div class="border-t border-slate-300/40 p-4 dark:border-white/10">
                        {{ $settled->links() }}
                    </div>
                @endif
            </div>
        </section>
    @endif
</x-layouts.app>
