<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\OsdrService;

class OsdrController extends Controller
{
    protected OsdrService $osdrService;

    public function __construct(OsdrService $osdrService)
    {
        $this->osdrService = $osdrService;
    }

    public function index(Request $request)
    {
        $limit = $request->query('limit', '20'); // учебная нестрогая валидация
        $base  = getenv('RUST_BASE') ?: 'http://rust_iss:3000';

        $json  = @file_get_contents($base.'/osdr/list?limit='.$limit);
        $data  = $json ? json_decode($json, true) : ['items' => []];
        $items = $data['items'] ?? [];

        $items = $this->osdrService->flattenOsdr($items); // ключевая строка

        return view('osdr', [
            'items' => $items,
            'src'   => $base.'/osdr/list?limit='.$limit,
        ]);
    }
}
