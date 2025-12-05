<?php

namespace App\Http\Controllers;

use App\Http\DTO\IssLastDto;
use App\Http\DTO\IssTrendDto;

class IssController extends Controller
{
    public function index()
    {
        $base = getenv('RUST_BASE') ?: 'http://rust_iss:3000';

        $last  = @file_get_contents($base.'/last');
        $trend = @file_get_contents($base.'/iss/trend');

        $lastJson  = $last  ? json_decode($last,  true) : [];
        $trendJson = $trend ? json_decode($trend, true) : [];

        $lastDto  = new IssLastDto($lastJson);
        $trendDto = new IssTrendDto($trendJson);

        return view('iss', ['last' => $lastDto->toArray(), 'trend' => $trendDto->toArray(), 'base' => $base]);
    }
}
