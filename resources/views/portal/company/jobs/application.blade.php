@extends('layouts.company')

@section('title', $application->name.' | Candidatura')

@section('content')
<div>
    <a href="{{ route('empresa.vagas.candidaturas', $job->id) }}" class="text-sm text-brand-600 hover:underline dark:text-brand-400">&larr; Voltar para as candidaturas</a>
    <h1 class="mt-2 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $application->name }}</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Candidatura para {{ $job->title }}</p>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <x-admin.card title="Contato">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">E-mail</dt>
                    <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                        <a href="mailto:{{ $application->email }}" class="text-brand-600 hover:underline dark:text-brand-400">{{ $application->email }}</a>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Telefone</dt>
                    <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">{{ $application->phone ?: 'Não informado' }}</dd>
                </div>
                @if ($application->linkedin_url)
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">LinkedIn</dt>
                        <dd class="mt-1 text-sm">
                            <a href="{{ $application->linkedin_url }}" target="_blank" rel="noopener"
                               class="text-brand-600 hover:underline dark:text-brand-400">{{ $application->linkedin_url }}</a>
                        </dd>
                    </div>
                @endif
            </dl>
        </x-admin.card>

        @if ($application->message)
            <x-admin.card title="Mensagem do candidato">
                <p class="whitespace-pre-line text-sm leading-relaxed text-gray-700 dark:text-gray-300">{{ $application->message }}</p>
            </x-admin.card>
        @endif
    </div>

    <div class="space-y-6">
        <x-admin.card title="Currículo">
            @if ($application->hasResume())
                <x-admin.button :href="route('empresa.vagas.curriculo', [$job->id, $application->id])"
                                variant="primary" icon="file" class="w-full">Baixar currículo</x-admin.button>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">Sem currículo anexado.</p>
            @endif
        </x-admin.card>

        <x-admin.card title="Registro">
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Recebida em</dt>
                    <dd class="mt-0.5 text-gray-800 dark:text-white/90">{{ $application->created_at->format('d/m/Y \à\s H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Consentimento LGPD</dt>
                    <dd class="mt-0.5 text-gray-800 dark:text-white/90">{{ $application->consented_at->format('d/m/Y \à\s H:i') }}</dd>
                </div>
            </dl>
        </x-admin.card>

        <x-admin.confirm-form :action="route('empresa.vagas.candidatura.destroy', [$job->id, $application->id])"
                              message="Remover a candidatura de {{ $application->name }}? O currículo será apagado.">Remover candidatura</x-admin.confirm-form>
    </div>
</div>
@endsection
