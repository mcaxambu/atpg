@extends('layouts.company')

@section('title', 'Vagas')

@section('content')
<x-admin.card :padding="false">
    <x-slot:title>{{ $jobs->total() }} {{ $jobs->total() === 1 ? 'vaga' : 'vagas' }}</x-slot:title>
    <x-slot:subtitle>Cada vaga ou alteração passa pela análise da associação antes de aparecer no portal.</x-slot:subtitle>
    <x-slot:actions>
        <x-admin.button :href="route('empresa.vagas.create')" variant="primary" icon="plus">Nova vaga</x-admin.button>
    </x-slot:actions>

    <div class="border-b border-gray-100 p-5 dark:border-gray-800">
        <x-admin.filter-bar :action="route('empresa.vagas.index')" placeholder="Buscar por título ou cidade..." />
    </div>

    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($jobs as $job)
            <div class="flex flex-col gap-4 p-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0 space-y-1">
                    <strong class="block truncate text-sm font-semibold text-gray-800 dark:text-white/90">{{ $job->title }}</strong>

                    <p class="truncate text-sm text-gray-500 dark:text-gray-400">
                        {{ $job->type->label() }} &middot; {{ $job->workplace->label() }} &middot; {{ $job->locationLabel() }}
                        @if ($job->salaryLabel()) &middot; {{ $job->salaryLabel() }} @endif
                    </p>

                    <p class="text-xs text-gray-400">
                        @if ($job->closes_at)
                            Encerra em {{ $job->closes_at->format('d/m/Y') }}
                            @if ($job->isExpired()) <span class="font-semibold text-error-600">(prazo vencido)</span> @endif
                        @else
                            Sem data de encerramento
                        @endif
                    </p>

                    @if ($job->isRejected() && $job->rejection_reason)
                        <p class="mt-1 rounded-lg bg-error-50 px-3 py-2 text-xs text-error-700 dark:bg-error-500/10 dark:text-error-300">
                            <strong>Não aprovada:</strong> {{ $job->rejection_reason }}
                        </p>
                    @endif
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-white/[0.06] dark:text-gray-300">
                        {{ $job->situationLabel() }}
                    </span>

                    <x-admin.button :href="route('empresa.vagas.candidaturas', $job->id)" icon="users">
                        Candidaturas ({{ $job->applications_count }})
                    </x-admin.button>

                    <x-admin.button :href="route('empresa.vagas.edit', $job->id)" icon="edit">Editar</x-admin.button>

                    @if ($job->is_active)
                        <x-admin.confirm-form :action="route('empresa.vagas.encerrar', $job->id)" method="patch"
                                              icon="check"
                                              message="Encerrar &quot;{{ $job->title }}&quot;? Ela sai do portal, mas as candidaturas recebidas continuam aqui.">Encerrar</x-admin.confirm-form>
                    @endif

                    <x-admin.confirm-form :action="route('empresa.vagas.destroy', $job->id)"
                                          message="Remover &quot;{{ $job->title }}&quot;?">Remover</x-admin.confirm-form>
                </div>
            </div>
        @empty
            <x-admin.empty-state icon="file" title="Nenhuma vaga cadastrada."
                                 description="Publique uma vaga para ela aparecer no portal da associação.">
                <x-slot:actions>
                    <x-admin.button :href="route('empresa.vagas.create')" variant="primary" icon="plus">Cadastrar vaga</x-admin.button>
                </x-slot:actions>
            </x-admin.empty-state>
        @endforelse
    </div>

    @if ($jobs->hasPages())
        <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $jobs->links() }}</div>
    @endif
</x-admin.card>
@endsection
