@extends($layout)

@section('title', $post->exists ? 'Editar coluna' : 'Nova coluna')

@section('content')
@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white';
    $action = $post->exists ? route($rotaBase.'.update', $post->id) : route($rotaBase.'.store');
@endphp

<div>
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">
        {{ $post->exists ? 'Editar coluna' : 'Nova coluna' }}
    </h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Assinada por <strong>{{ $columnist?->byline }}</strong>.
        Depois de salvar, o texto vai para análise da associação antes de aparecer no portal.
    </p>
</div>

@if ($post->exists && $post->isRejected() && $post->rejection_reason)
    <div class="rounded-lg border border-error-500/30 bg-error-50 px-4 py-3 text-sm text-error-700 dark:bg-error-500/10 dark:text-error-300">
        <strong>Não aprovada:</strong> {{ $post->rejection_reason }}
    </div>
@endif

@include('portal.admin.partials.errors')

<form method="post" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if ($post->exists) @method('put') @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-admin.card title="O texto">
                <x-admin.field label="Título" name="title" required>
                    <input type="text" name="title" value="{{ old('title', $post->title) }}" required class="{{ $input }}">
                </x-admin.field>

                <x-admin.field label="Resumo" name="excerpt" class="mt-4"
                               hint="Opcional. Aparece na listagem e no compartilhamento em redes sociais.">
                    <textarea name="excerpt" rows="3" maxlength="500" class="{{ $input }}">{{ old('excerpt', $post->excerpt) }}</textarea>
                </x-admin.field>

                <x-admin.field label="Coluna" name="body" required class="mt-4"
                               hint="Mínimo de 200 caracteres. Use os botões para formatar títulos, listas e destaques.">
                    <textarea name="body" data-editor rows="16" required class="{{ $input }}">{{ old('body', $post->body) }}</textarea>
                </x-admin.field>
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card title="Imagem de capa">
                @if ($post->cover_image)
                    <img src="{{ asset('storage/'.$post->cover_image) }}" alt="Capa atual"
                         class="mb-3 h-32 w-full rounded-lg border border-gray-200 object-cover dark:border-gray-700">

                    <label class="mb-3 flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                        <input type="checkbox" name="remove_cover" value="1"
                               class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                        Remover a capa atual
                    </label>
                @endif

                <x-admin.field name="cover_image" hint="JPG, PNG ou WEBP, até 3 MB. Opcional.">
                    <input type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp"
                           class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-600 hover:file:bg-brand-100 dark:file:bg-brand-500/15 dark:file:text-brand-300">
                </x-admin.field>
            </x-admin.card>

            <x-admin.card title="Como funciona">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Ao salvar, a coluna fica <strong>em análise</strong>. A diretoria lê e publica —
                    ou devolve com um motivo, que aparece aqui para você ajustar.
                </p>
                <p class="mt-3 rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-500 dark:bg-white/[0.03] dark:text-gray-400">
                    Editar uma coluna já publicada devolve o texto para análise, e ele sai do ar até ser aprovado de novo.
                </p>
            </x-admin.card>

            <div class="flex flex-col gap-2">
                <x-admin.button type="submit" variant="primary" icon="check">
                    {{ $post->exists ? 'Salvar e enviar para análise' : 'Enviar para análise' }}
                </x-admin.button>
                <x-admin.button :href="route($rotaBase.'.index')" variant="ghost">Cancelar</x-admin.button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
    @vite('resources/js/editor.js')
@endpush
