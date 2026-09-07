<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Qr\GenerateStationQr;
use App\Http\Controllers\Controller;
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * PNG for general use, SVG for engraving/sign-printing vendors who want a
 * vector. See plan/phases/phase-08-admin-qr.md M8.4.
 */
class AdminQrDownloadController extends Controller
{
    public function __invoke(Request $request, Station $station, GenerateStationQr $generate): Response
    {
        $format = $request->query('format') === 'svg' ? 'svg' : 'png';
        $result = $generate($station, $format);

        $filename = Str::slug($station->code).'-qr.'.$format;

        return response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
