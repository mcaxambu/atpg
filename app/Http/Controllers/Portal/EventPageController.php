<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Support\Portal\PortalData;

class EventPageController extends Controller
{
    public function index(PortalData $portalData)
    {
        return view('portal.events', $portalData->merge([
            'events' => Event::query()
                ->published()
                ->orderBy('event_date')
                ->orderBy('starts_at')
                ->get(),
        ]));
    }

    public function show(Event $event, PortalData $portalData)
    {
        abort_unless($event->is_published, 404);

        return view('portal.events-show', $portalData->merge(['event' => $event]));
    }
}
