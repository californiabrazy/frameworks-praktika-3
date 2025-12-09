<?php

namespace App\Http\Services;

use App\Http\Clients\JwstClient;
use App\Http\Repositories\CmsBlockRepository;
use App\Http\Repositories\TelemetryRepository;
use Illuminate\Http\Request;

class DashboardService
{
    protected CmsBlockRepository $cmsBlockRepository;
    protected TelemetryRepository $telemetryRepository;
    protected JwstClient $jwstClient;

    public function __construct(CmsBlockRepository $cmsBlockRepository, TelemetryRepository $telemetryRepository, JwstClient $jwstClient)
    {
        $this->cmsBlockRepository = $cmsBlockRepository;
        $this->telemetryRepository = $telemetryRepository;
        $this->jwstClient = $jwstClient;
    }

    private function base(): string
    {
        return getenv('RUST_BASE') ?: 'http://rust_iss:3000';
    }

    private function getJson(string $url, array $qs = []): array
    {
        if ($qs) $url .= (str_contains($url,'?')?'&':'?') . http_build_query($qs);
        $raw = @file_get_contents($url);
        return $raw ? (json_decode($raw, true) ?: []) : [];
    }

    public function getDashboardData(): array
    {
        $b = $this->base();
        $iss = $this->getJson($b.'/last');
        $trend = [];

        $csvData = $this->telemetryRepository->getCsvData();

        $cmsBlocks = $this->cmsBlockRepository->getActiveBlocksBySlug('dashboard_experiment');

        return [
            'iss' => $iss,
            'trend' => $trend,
            'csv_data' => $csvData,
            'cms_blocks' => $cmsBlocks,
        ];
    }

    public function getJwstFeed(Request $r): array
    {
        $src = $r->query('source', 'jpg');
        $sfx = trim((string)$r->query('suffix', ''));
        $prog = trim((string)$r->query('program', ''));
        $instF = strtoupper(trim((string)$r->query('instrument', '')));
        $page = max(1, (int)$r->query('page', 1));
        $per = max(1, min(60, (int)$r->query('perPage', 24)));

        $path = 'all/type/jpg';
        if ($src === 'suffix' && $sfx !== '') $path = 'all/suffix/'.ltrim($sfx,'/');
        if ($src === 'program' && $prog !== '') $path = 'program/id/'.rawurlencode($prog);

        $resp = $this->jwstClient->get($path, ['page'=>$page, 'perPage'=>$per]);
        $list = $resp['body'] ?? ($resp['data'] ?? (is_array($resp) ? $resp : []));

        $items = [];
        foreach ($list as $it) {
            if (!is_array($it)) continue;
            $url = $it['location'] ?? $it['url'] ?? null;
            if (!$url) continue;

            $instList = [];
            foreach (($it['details']['instruments'] ?? []) as $I) {
                if (is_array($I) && !empty($I['instrument'])) $instList[] = strtoupper($I['instrument']);
            }
            if ($instF && $instList && !in_array($instF, $instList, true)) continue;

            $items[] = [
                'url' => $url,
                'obs' => (string)($it['observation_id'] ?? $it['observationId'] ?? ''),
                'program' => (string)($it['program'] ?? ''),
                'suffix' => (string)($it['details']['suffix'] ?? $it['suffix'] ?? ''),
                'inst' => $instList,
                'caption' => (string)($it['caption'] ?? $it['description'] ?? ''),
            ];

            if (count($items) >= $per) break;
        }

        return [
            'source' => $path,
            'count' => count($items),
            'items' => $items,
        ];
    }

    public function downloadCsv($filename): string
    {
        $path = '/data/csv/' . $filename;

        if (!file_exists($path)) {
            abort(404, 'File not found');
        }

        return $path;
    }
}
