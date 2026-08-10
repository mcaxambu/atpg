@props(['action', 'subject'])

{{--
    Rejeicao exige motivo: o texto vai por e-mail para quem se cadastrou,
    entao um "rejeitado" sem explicacao nao e aceitavel.
--}}
<div x-data="{ open: false }" class="inline-flex">
    <x-admin.button variant="danger" icon="x" @click="open = true">Rejeitar</x-admin.button>

    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-999999 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/60" @click="open = false"></div>

            <form method="post" action="{{ $action }}" x-show="open" x-transition
                  class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-theme-lg dark:bg-gray-900">
                @csrf
                @method('patch')

                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Rejeitar {{ $subject }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    O motivo abaixo será enviado por e-mail ao responsável pelo cadastro.
                </p>

                <x-admin.field label="Motivo da rejeição" name="rejection_reason" class="mt-4" required
                               hint="Mínimo de 10 caracteres. Seja específico para que o cadastro possa ser corrigido.">
                    <textarea name="rejection_reason" rows="4" required minlength="10" maxlength="1000"
                              class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                              placeholder="Ex.: o CNPJ informado não corresponde à razão social enviada."></textarea>
                </x-admin.field>

                <div class="mt-5 flex justify-end gap-2">
                    <x-admin.button variant="ghost" @click="open = false">Cancelar</x-admin.button>
                    <x-admin.button type="submit" variant="danger" icon="x">Confirmar rejeição</x-admin.button>
                </div>
            </form>
        </div>
    </template>
</div>
