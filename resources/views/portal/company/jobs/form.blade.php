@extends('layouts.company')

@section('title', $job->exists ? 'Editar vaga' : 'Nova vaga')

@section('content')
@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white';
    $action = $job->exists ? route('empresa.vagas.update', $job->id) : route('empresa.vagas.store');
@endphp

<div>
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">
        {{ $job->exists ? 'Editar vaga' : 'Nova vaga' }}
    </h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Depois de salvar, a vaga vai para análise da associação antes de aparecer no portal.
    </p>
</div>

@if ($job->exists && $job->isRejected() && $job->rejection_reason)
    <div class="rounded-lg border border-error-500/30 bg-error-50 px-4 py-3 text-sm text-error-700 dark:bg-error-500/10 dark:text-error-300">
        <strong>Não aprovada:</strong> {{ $job->rejection_reason }}
    </div>
@endif

<form method="post" action="{{ $action }}" class="space-y-6"
      x-data="{ mostrarSalario: {{ old('show_salary', $job->show_salary) ? 'true' : 'false' }} }">
    @csrf
    @if ($job->exists) @method('put') @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-admin.card title="A vaga">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-admin.field label="Título" name="title" required class="sm:col-span-2"
                                   hint="Como o cargo é conhecido no mercado. Ex.: Desenvolvedor(a) back-end Python">
                        <input type="text" name="title" value="{{ old('title', $job->title) }}" required class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Tipo de contrato" name="type" required>
                        <select name="type" class="{{ $input }}" required>
                            @foreach ($types as $type)
                                <option value="{{ $type->value }}" @selected(old('type', $job->type?->value) === $type->value)>{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </x-admin.field>

                    <x-admin.field label="Modelo de trabalho" name="workplace" required>
                        <select name="workplace" class="{{ $input }}" required>
                            @foreach ($workplaces as $place)
                                <option value="{{ $place->value }}" @selected(old('workplace', $job->workplace?->value) === $place->value)>{{ $place->label() }}</option>
                            @endforeach
                        </select>
                    </x-admin.field>

                    <x-admin.field label="Senioridade" name="seniority"
                                   hint="Opcional. Ex.: Júnior, Pleno, Sênior.">
                        <input type="text" name="seniority" value="{{ old('seniority', $job->seniority) }}" maxlength="32" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Data de encerramento" name="closes_at"
                                   hint="Opcional. Passada a data, a vaga sai do portal sozinha.">
                        <input type="date" name="closes_at" class="{{ $input }}"
                               value="{{ old('closes_at', $job->closes_at?->format('Y-m-d')) }}">
                    </x-admin.field>

                    <x-admin.field label="Cidade" name="city" class="sm:col-span-1">
                        <input type="text" name="city" value="{{ old('city', $job->city ?: 'Ponta Grossa') }}" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="UF" name="state">
                        <input type="text" name="state" value="{{ old('state', $job->state ?: 'PR') }}" maxlength="2" class="{{ $input }}">
                    </x-admin.field>
                </div>
            </x-admin.card>

            <x-admin.card title="Descrição">
                <x-admin.field label="O que a pessoa vai fazer" name="description" required
                               hint="Mínimo de 40 caracteres. Descreva o dia a dia, o time e o que se espera da pessoa.">
                    <textarea name="description" data-editor rows="7" required class="{{ $input }}">{{ old('description', $job->description) }}</textarea>
                </x-admin.field>

                <x-admin.field label="Requisitos" name="requirements" class="mt-4"
                               hint="Opcional. Uma linha por requisito.">
                    <textarea name="requirements" rows="5" class="{{ $input }}">{{ old('requirements', $job->requirements) }}</textarea>
                </x-admin.field>

                <x-admin.field label="Benefícios" name="benefits" class="mt-4"
                               hint="Opcional. Uma linha por benefício.">
                    <textarea name="benefits" rows="4" class="{{ $input }}">{{ old('benefits', $job->benefits) }}</textarea>
                </x-admin.field>
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card title="Faixa salarial">
                <label class="flex items-center gap-3 rounded-lg border border-gray-200 px-4 py-3 dark:border-gray-700">
                    <input type="checkbox" name="show_salary" value="1" x-model="mostrarSalario"
                           class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Mostrar salário na vaga</span>
                </label>

                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Vagas com salário visível costumam receber mais candidaturas. Se deixar desmarcado,
                    os valores ficam guardados mas não aparecem no portal.
                </p>

                <div x-show="mostrarSalario" x-cloak class="mt-4 grid gap-4">
                    <x-admin.field label="De (R$)" name="salary_min">
                        <input type="number" name="salary_min" min="0" step="100"
                               value="{{ old('salary_min', $job->salary_min) }}" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Até (R$)" name="salary_max">
                        <input type="number" name="salary_max" min="0" step="100"
                               value="{{ old('salary_max', $job->salary_max) }}" class="{{ $input }}">
                    </x-admin.field>
                </div>
            </x-admin.card>

            <x-admin.card title="Como receberá os candidatos">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    As candidaturas chegam pelo próprio portal, com currículo anexado, e ficam
                    disponíveis na lista de candidaturas desta vaga.
                </p>
                <p class="mt-3 rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-500 dark:bg-white/[0.03] dark:text-gray-400">
                    Os dados do candidato são pessoais: só a sua empresa tem acesso a eles. A associação
                    vê apenas quantas candidaturas a vaga recebeu.
                </p>
            </x-admin.card>

            <div class="flex flex-col gap-2">
                <x-admin.button type="submit" variant="primary" icon="check">
                    {{ $job->exists ? 'Salvar e enviar para análise' : 'Publicar e enviar para análise' }}
                </x-admin.button>
                <x-admin.button :href="route('empresa.vagas.index')" variant="ghost">Cancelar</x-admin.button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
    @vite('resources/js/editor.js')
@endpush
