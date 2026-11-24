@props(['status'])

@php
    $currentStep = $status->progressStep();
    $steps = \App\Participation\Entities\ParticipantProgressStatus::progressSteps();
@endphp

<ol
    {{ $attributes->merge(['class' => 'mx-auto flex w-full max-w-3xl items-start']) }}
    aria-label="Participant progress"
    data-participant-progress
    data-current-step="{{ $currentStep }}"
>
    @foreach ($steps as $step)
        @php
            $isComplete = $step->progressStep() < $currentStep;
            $isCurrent = $step->progressStep() === $currentStep;

            $circleClasses = $isComplete
                ? 'border-emerald-500 bg-emerald-500 text-white dark:border-emerald-400 dark:bg-emerald-400 dark:text-emerald-950'
                : 'border-slate-300 bg-white/60 text-slate-500 dark:border-white/15 dark:bg-white/5 dark:text-slate-400';

            $labelClasses = $isComplete
                ? 'text-slate-700 dark:text-slate-200'
                : 'text-slate-500 dark:text-slate-400';
        @endphp

        <li class="relative flex flex-1 flex-col items-center text-center" data-progress-step="{{ $step->value }}" data-progress-state="{{ $isComplete ? 'complete' : ($isCurrent ? 'current' : 'upcoming') }}" @if ($isCurrent) aria-current="step" @endif>
            @if (! $loop->last)
                <span class="absolute left-1/2 top-3.5 h-px w-full {{ $isComplete ? 'bg-emerald-500/60 dark:bg-emerald-400/50' : 'bg-slate-300/70 dark:bg-white/15' }}" aria-hidden="true"></span>
            @endif

            <span class="relative z-10 flex h-7 w-7 items-center justify-center rounded-full border text-xs font-semibold {{ $circleClasses }}">
                @if ($isComplete)
                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0l-3.5-3.5a1 1 0 1 1 1.4-1.4l2.8 2.8 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd" />
                    </svg>
                @else
                    {{ $step->progressStep() }}
                @endif
            </span>

            <span class="relative z-10 mt-2 text-[0.7rem] font-medium leading-tight {{ $labelClasses }}">{{ $step->label() }}</span>
        </li>
    @endforeach
</ol>
