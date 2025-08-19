<?php

namespace App\Http\Middleware;

use App\Enum\Status;
use App\Models\Period;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CheckActivePeriod
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $activePeriod = Period::where('status', Status::ACTIVE)
                                ->where('start_date', '<=', now())
                                ->where('end_date', '>=', now())
                                ->first();

        if (!$activePeriod) {
            abort(403, 'Saat ini tidak ada periode penilaian yang aktif atau periode telah berakhir.');
        }

        Inertia::share('activePeriod', [
            'id' => $activePeriod->id,
            'name' => $activePeriod->name,
        ]);

        return $next($request);
    }
}
