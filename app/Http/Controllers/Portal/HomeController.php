<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Support\Portal\PortalData;

class HomeController extends Controller
{
    public function __invoke(PortalData $portalData)
    {
        return view('portal.home', $portalData->shared());
    }
}
