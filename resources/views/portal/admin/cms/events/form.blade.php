@extends('layouts.admin')

@section('title', ($event->exists ? 'Editar evento' : 'Novo evento') . ' | Portal Associação Tech PG')
@section('page_heading', $event->exists ? 'Editar evento' : 'Novo evento')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <span class="text-xs font-semibold uppercase tracking-wide text-brand-500">CMS do portal</span>
            <h2 class="mt-1 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $event->exists ? 'Editar evento' : 'Novo evento' }}</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Eventos publicados aparecem na agenda pública do portal.</p>
        </div>
        <a class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]" href="{{ route('admin.cms.events.index') }}">Voltar</a>
    </div>


    <form class="space-y-6" method="post" action="{{ $event->exists ? route('admin.cms.events.update', $event) : route('admin.cms.events.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($event->exists) @method('put') @endif

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <label class="block lg:col-span-2">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Titulo</span>
                    <input class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="title" value="{{ old('title', $event->title) }}" required>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo</span>
                    <input class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="type" value="{{ old('type', $event->type ?: 'Evento') }}" required>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Local</span>
                    <input class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="location" value="{{ old('location', $event->location) }}">
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data</span>
                    <input class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="event_date" type="date" value="{{ old('event_date', $event->event_date?->format('Y-m-d')) }}">
                </label>
                <div class="grid grid-cols-2 gap-4">
                    <label class="block">
                        <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Inicio</span>
                        <input class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="starts_at" type="time" value="{{ old('starts_at', $event->starts_at?->format('H:i')) }}">
                    </label>
                    <label class="block">
                        <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Fim</span>
                        <input class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="ends_at" type="time" value="{{ old('ends_at', $event->ends_at?->format('H:i')) }}">
                    </label>
                </div>
                <label class="block lg:col-span-2">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Link de inscricao</span>
                    <input class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="registration_url" value="{{ old('registration_url', $event->registration_url) }}" placeholder="https://">
                </label>
                <label class="block lg:col-span-2">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição</span>
                    <textarea class="min-h-40 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="description" required>{{ old('description', $event->description) }}</textarea>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Imagem de capa</span>
                    <input class="block w-full rounded-lg border border-gray-300 bg-transparent text-sm text-gray-800 file:mr-5 file:border-0 file:bg-gray-100 file:px-4 file:py-3 file:text-sm file:font-medium file:text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="cover_image" type="file" accept=".jpg,.jpeg,.png,.webp,image/*">
                </label>
                <div class="grid gap-3">
                    <label class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm font-medium text-gray-700 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-300"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $event->exists ? $event->is_published : false))> Publicar no portal</label>
                    <label class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm font-medium text-gray-700 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-300"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $event->exists ? $event->is_featured : false))> Destacar evento</label>
                </div>
            </div>
        </section>

        <div class="flex flex-wrap justify-end gap-3">
            <a class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" href="{{ route('admin.cms.events.index') }}">Cancelar</a>
            <button class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600" type="submit">Salvar evento</button>
        </div>
    </form>
</div>
@endsection
