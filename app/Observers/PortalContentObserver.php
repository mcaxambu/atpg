<?php

namespace App\Observers;

use App\Support\Portal\PortalData;
use Illuminate\Database\Eloquent\Model;

/**
 * Invalida o cache do portal sempre que um conteudo publicavel muda,
 * para que uma aprovacao ou publicacao apareca no site imediatamente.
 */
class PortalContentObserver
{
    public function saved(Model $model): void
    {
        PortalData::flush();
    }

    public function deleted(Model $model): void
    {
        PortalData::flush();
    }

    public function restored(Model $model): void
    {
        PortalData::flush();
    }

    public function forceDeleted(Model $model): void
    {
        PortalData::flush();
    }
}
