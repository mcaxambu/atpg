@props(['especialidades', 'marcadas' => [], 'nome' => 'especialidades'])

{{--
    Caixinhas do vocabulário controlado de especialidades.

    O mesmo componente serve para a empresa dizer o que FAZ e para a indústria
    dizer do que PRECISA — é por os dois lados usarem a mesma lista que o
    cruzamento existe. Texto livre não cruza com nada.
--}}
@if ($especialidades->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma especialidade cadastrada ainda.</p>
@else
    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($especialidades as $especialidade)
            <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                <input type="checkbox" name="{{ $nome }}[]" value="{{ $especialidade->id }}"
                       @checked(in_array($especialidade->id, (array) $marcadas))
                       class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                {{ $especialidade->name }}
            </label>
        @endforeach
    </div>
@endif
