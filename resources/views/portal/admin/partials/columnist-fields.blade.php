{{--
    Marcacao de colunista, usada no cadastro de membro e no de empresa.

    Nao cria usuario: quem for marcado passa a ver a aba "Minhas colunas" no
    painel que ja usa, e escreve por la. O texto vai para analise da diretoria
    antes de aparecer no portal.

    Espera:
      $columnist  perfil ja existente (ou null)
      $tipo       'membro' ou 'empresa', so para o texto de ajuda
--}}
@php
    $marcado = (bool) old('is_columnist', $columnist?->is_active);
    $ehEmpresa = $tipo === 'empresa';
@endphp

<div x-data="{ colunista: {{ $marcado ? 'true' : 'false' }} }" class="space-y-3">
    <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition-colors"
           :class="colunista
               ? 'border-brand-400 bg-brand-50 dark:border-brand-500 dark:bg-brand-500/10'
               : 'border-gray-200 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-white/[0.03]'">
        <input type="checkbox" name="is_columnist" value="1" x-model="colunista"
               class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
        <span>
            <span class="block text-sm font-medium text-gray-800 dark:text-white">É colunista do portal</span>
            <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">
                Ganha uma página em <strong>/colunas</strong> e a aba "Minhas colunas" no painel,
                para escrever os próprios textos. Cada coluna passa pela análise da diretoria.
            </span>
        </span>
    </label>

    <div x-show="colunista" x-cloak>
        <x-admin.field
            label="{{ $ehEmpresa ? 'Quem assina pela empresa' : 'Assina como' }}"
            name="columnist_name"
            hint="{{ $ehEmpresa
                ? 'Opcional. A coluna assina com o nome da empresa; preencha para creditar a pessoa que escreve — sai como \'Nome — Empresa\'.'
                : 'Opcional. Deixe em branco para assinar com o nome do cadastro. Só preencha se a assinatura for diferente.' }}">
            <input type="text" name="columnist_name" maxlength="255"
                   value="{{ old('columnist_name', $columnist?->name) }}"
                   class="h-11 w-full rounded-lg border border-gray-200 bg-white px-4 text-sm text-gray-800 placeholder-gray-400 outline-none transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
        </x-admin.field>

        @if ($columnist && $columnist->posts()->exists())
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                Já assina {{ $columnist->posts()->count() }}
                {{ $columnist->posts()->count() === 1 ? 'coluna' : 'colunas' }}.
                Desmarcar a caixa tira a página do ar, mas não apaga os textos.
            </p>
        @endif
    </div>
</div>
