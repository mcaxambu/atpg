@extends($layout)

@section('title', $topic->exists ? 'Editar tópico' : 'Abrir tópico')

@section('content')
@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white';
    $action = $topic->exists ? route($rotaBase.'.update', $topic->slug) : route($rotaBase.'.store');
@endphp

<form method="post" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($topic->exists) @method('put') @endif

    <x-admin.card :title="$topic->exists ? 'Editar tópico' : 'Abrir tópico'"
                  subtitle="O texto fica visível para os associados assim que você publicar — não passa por aprovação.">
        <div class="space-y-4">
            <x-admin.field label="Assunto" name="forum_category_id" required>
                <select name="forum_category_id" class="{{ $input }}">
                    <option value="">Escolha o assunto</option>
                    @foreach ($categorias as $categoria)
                        <option value="{{ $categoria->id }}" @selected((int) old('forum_category_id', $topic->forum_category_id) === $categoria->id)>
                            {{ $categoria->name }}
                        </option>
                    @endforeach
                </select>
            </x-admin.field>

            <x-admin.field label="Título" name="title" required hint="Uma frase que diga do que se trata.">
                <input type="text" name="title" maxlength="160" required value="{{ old('title', $topic->title) }}"
                       placeholder="Alguém já usou nota fiscal de serviço em Ponta Grossa?" class="{{ $input }}">
            </x-admin.field>

            <x-admin.field label="Texto" name="body" required>
                <textarea name="body" data-editor rows="12" required class="{{ $input }}">{{ old('body', $topic->body) }}</textarea>
            </x-admin.field>
        </div>
    </x-admin.card>

    <div class="flex flex-wrap items-center gap-3">
        <x-admin.button type="submit" variant="primary" icon="check">
            {{ $topic->exists ? 'Salvar' : 'Publicar tópico' }}
        </x-admin.button>

        <x-admin.button :href="$topic->exists ? route($rotaBase.'.show', $topic->slug) : route($rotaBase.'.index')" variant="ghost">Cancelar</x-admin.button>
    </div>
</form>
@endsection

@push('scripts')
    @vite('resources/js/editor.js')
@endpush
