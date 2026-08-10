@if (session('status'))
    <div x-data="{ show: true }" x-show="show" x-transition
         class="flex items-start gap-3 rounded-2xl border border-success-200 bg-success-50 px-5 py-4 dark:border-success-500/30 dark:bg-success-500/10">
        <x-admin.icon name="check" class="mt-0.5 h-5 w-5 shrink-0 text-success-600 dark:text-success-400" />
        <p class="flex-1 text-sm font-medium text-success-800 dark:text-success-200">{{ session('status') }}</p>
        <button type="button" @click="show = false" class="text-success-600 hover:text-success-800 dark:text-success-400">
            <x-admin.icon name="x" class="h-4 w-4" />
            <span class="sr-only">Fechar aviso</span>
        </button>
    </div>
@endif

@if ($errors->any())
    <div class="rounded-2xl border border-error-200 bg-error-50 px-5 py-4 dark:border-error-500/30 dark:bg-error-500/10">
        <div class="flex items-center gap-2">
            <x-admin.icon name="alert" class="h-5 w-5 text-error-600 dark:text-error-400" />
            <strong class="text-sm font-semibold text-error-800 dark:text-error-200">
                {{ $errors->count() === 1 ? 'Corrija o campo abaixo:' : 'Corrija os '.$errors->count().' campos abaixo:' }}
            </strong>
        </div>
        <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-error-700 dark:text-error-300">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
