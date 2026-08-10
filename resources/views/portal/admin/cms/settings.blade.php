@extends('layouts.admin')

@section('title', 'Configurações do portal')
@section('page_heading', 'Configurações do portal')

@section('content')
    @php
        $value = fn (string $key, string $default = '') => old($key, $settings[$key] ?? $default);
    @endphp

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <span class="text-sm font-semibold uppercase tracking-wide text-brand-500">CMS do Website</span>
            <h2 class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">Identidade, contatos e redes</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Estas informações alimentam o cabeçalho, rodapé e pontos institucionais do portal público.</p>
        </div>
        <a href="{{ route('admin.cms.dashboard') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-gray-200 px-5 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">Voltar ao CMS</a>
    </div>

    @if (session('status'))
        <div class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700 dark:border-green-500/30 dark:bg-green-500/10 dark:text-green-300">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">
            Revise os campos destacados antes de salvar.
        </div>
    @endif

    <form method="post" action="{{ route('admin.cms.settings.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('put')

        <section class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Identidade institucional</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Nome público, subtítulo, texto-base e marcas visuais.</p>
            </div>

            <div class="grid gap-5 lg:grid-cols-2">
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200">Nome do portal *</span>
                    <input name="site_name" value="{{ $value('site_name') }}" required class="h-12 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    @error('site_name') <small class="mt-1 block text-red-500">{{ $message }}</small> @enderror
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200">Subtitulo</span>
                    <input name="site_tagline" value="{{ $value('site_tagline') }}" class="h-12 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    @error('site_tagline') <small class="mt-1 block text-red-500">{{ $message }}</small> @enderror
                </label>

                <label class="block lg:col-span-2">
                    <span class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200">Texto institucional</span>
                    <textarea name="institutional_text" rows="4" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white">{{ $value('institutional_text') }}</textarea>
                    @error('institutional_text') <small class="mt-1 block text-red-500">{{ $message }}</small> @enderror
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200">Anos de ecossistema</span>
                    <input name="ecosystem_years" type="number" min="0" max="100" value="{{ $value('ecosystem_years', '10') }}" class="h-12 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    @error('ecosystem_years') <small class="mt-1 block text-red-500">{{ $message }}</small> @enderror
                </label>

                <div>
                    <span class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200">Logo atual</span>
                    <div class="flex min-h-24 items-center gap-4 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900">
                        @if (! empty($settings['logo_path']))
                            <img src="{{ asset('storage/' . $settings['logo_path']) }}" alt="Logo atual" class="h-16 w-16 rounded-xl bg-white object-contain p-2">
                        @else
                            <span class="grid h-16 w-16 place-items-center rounded-xl bg-brand-500 text-sm font-bold text-white">PG</span>
                        @endif
                        <label class="flex-1">
                            <span class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200">Nova logo</span>
                            <input type="file" name="logo" accept="image/*" class="w-full rounded-xl border border-gray-300 bg-white text-sm text-gray-700 file:mr-4 file:h-12 file:border-0 file:bg-gray-100 file:px-4 file:text-sm file:font-semibold dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:file:bg-gray-800">
                        </label>
                    </div>
                    @error('logo') <small class="mt-1 block text-red-500">{{ $message }}</small> @enderror
                </div>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200">Favicon</span>
                    <input type="file" name="favicon" accept="image/*,.ico" class="w-full rounded-xl border border-gray-300 bg-white text-sm text-gray-700 file:mr-4 file:h-12 file:border-0 file:bg-gray-100 file:px-4 file:text-sm file:font-semibold dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:file:bg-gray-800">
                    @error('favicon') <small class="mt-1 block text-red-500">{{ $message }}</small> @enderror
                </label>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Contato oficial</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Dados exibidos no rodapé e usados como referencia institucional.</p>
            </div>

            <div class="grid gap-5 lg:grid-cols-2">
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200">E-mail</span>
                    <input name="email" type="email" value="{{ $value('email') }}" class="h-12 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    @error('email') <small class="mt-1 block text-red-500">{{ $message }}</small> @enderror
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200">CNPJ</span>
                    <input name="cnpj" data-mask="cnpj" value="{{ $value('cnpj') }}" placeholder="00.000.000/0000-00" class="h-12 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    @error('cnpj') <small class="mt-1 block text-red-500">{{ $message }}</small> @enderror
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200">Telefone</span>
                    <input name="phone" data-mask="phone" value="{{ $value('phone') }}" placeholder="(42) 99999-9999" class="h-12 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    @error('phone') <small class="mt-1 block text-red-500">{{ $message }}</small> @enderror
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200">WhatsApp</span>
                    <input name="whatsapp" data-mask="phone" value="{{ $value('whatsapp') }}" placeholder="(42) 99999-9999" class="h-12 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    @error('whatsapp') <small class="mt-1 block text-red-500">{{ $message }}</small> @enderror
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200">Cidade</span>
                    <input name="city" value="{{ $value('city') }}" class="h-12 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    @error('city') <small class="mt-1 block text-red-500">{{ $message }}</small> @enderror
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200">Endereço</span>
                    <input name="address" value="{{ $value('address') }}" class="h-12 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    @error('address') <small class="mt-1 block text-red-500">{{ $message }}</small> @enderror
                </label>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Redes sociais</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Links usados no rodapé e chamadas públicas.</p>
            </div>

            <div class="grid gap-5 lg:grid-cols-3">
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200">LinkedIn</span>
                    <input name="linkedin_url" type="url" value="{{ $value('linkedin_url') }}" placeholder="https://" class="h-12 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    @error('linkedin_url') <small class="mt-1 block text-red-500">{{ $message }}</small> @enderror
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200">Instagram</span>
                    <input name="instagram_url" type="url" value="{{ $value('instagram_url') }}" placeholder="https://" class="h-12 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    @error('instagram_url') <small class="mt-1 block text-red-500">{{ $message }}</small> @enderror
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200">WhatsApp público</span>
                    <input name="whatsapp_url" type="url" value="{{ $value('whatsapp_url') }}" placeholder="https://wa.me/..." class="h-12 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    @error('whatsapp_url') <small class="mt-1 block text-red-500">{{ $message }}</small> @enderror
                </label>
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.cms.dashboard') }}" class="inline-flex h-12 items-center justify-center rounded-xl border border-gray-200 px-6 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">Cancelar</a>
            <button type="submit" class="inline-flex h-12 items-center justify-center rounded-xl bg-brand-500 px-6 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-600">Salvar configurações</button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        const onlyDigits = (value) => value.replace(/\D/g, '');

        document.querySelectorAll('[data-mask="cnpj"]').forEach((input) => {
            input.addEventListener('input', () => {
                input.value = onlyDigits(input.value)
                    .slice(0, 14)
                    .replace(/^(\d{2})(\d)/, '$1.$2')
                    .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
                    .replace(/\.(\d{3})(\d)/, '.$1/$2')
                    .replace(/(\d{4})(\d)/, '$1-$2');
            });
        });

        document.querySelectorAll('[data-mask="phone"]').forEach((input) => {
            input.addEventListener('input', () => {
                const digits = onlyDigits(input.value).slice(0, 11);
                input.value = digits.length > 10
                    ? digits.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3')
                    : digits.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3').replace(/-$/, '');
            });
        });
    </script>
@endpush
