@extends('layouts.app')

@section('content')
<div class="container py-5">
  <h3 class="mb-4 text-center">МКС (ISS) — текущее положение и движение</h3>

  {{-- КОМПАКТНЫЙ БЛОК: КАРТА + ГРАФИКИ (стал заметно меньше) --}}
  <div class="row g-3 mb-4">
    <div class="col-12">
      <div class="card shadow">
        <div class="card-body p-3">
          <h5 class="card-title mb-3">Положение МКС в реальном времени</h5>
          <div id="map" class="rounded border shadow-sm mb-3" style="height: 340px;"></div>
          
          <div class="row g-3">
            <div class="col-md-6">
              <p>Скорость:</p>
              <canvas id="issSpeedChart" height="100"></canvas>
            </div>
            <div class="col-md-6">
              <p>Высота:</p>
              <canvas id="issAltChart" height="100"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ДВЕ КАРТОЧКИ С ДАННЫМИ ВНИЗУ --}}
  <div class="row g-4">
    <div class="col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h5 class="card-title">Текущие данные</h5>
          @if(!empty($last['payload']))
            <ul class="list-group list-group-flush">
              <li class="list-group-item d-flex justify-content-between">
                <span>Широта:</span>
                <strong>{{ $last['payload']['latitude'] ?? '—' }}°</strong>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span>Долгота:</span>
                <strong>{{ $last['payload']['longitude'] ?? '—' }}°</strong>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span>Высота:</span>
                <strong>{{ $last['payload']['altitude'] ?? '—' }} км</strong>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span>Скорость:</span>
                <strong>{{ $last['payload']['velocity'] ?? '—' }} км/ч</strong>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span>Время:</span>
                <span>{{ $last['fetched_at'] ? \Carbon\Carbon::parse($last['fetched_at'])->format('d.m.Y H:i:s') : '—' }}</span>
              </li>
            </ul>
          @else
            <div class="text-muted text-center py-4">Нет данных</div>
          @endif
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h5 class="card-title">Движение за последний период</h5>
          @if(!empty($trend))
            <ul class="list-group list-group-flush">
              <li class="list-group-item d-flex justify-content-between">
                <span>Станция движется:</span>
                <strong class="{{ ($trend['movement'] ?? false) ? 'text-success' : 'text-danger' }}">
                  {{ ($trend['movement'] ?? false) ? 'Да' : 'Нет' }}
                </strong>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span>Смещение:</span>
                <strong>{{ number_format($trend['delta_km'] ?? 0, 3, '.', ' ') }} км</strong>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span>Интервал:</span>
                <strong>{{ $trend['dt_sec'] ?? 0 }} сек</strong>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span>Ср. скорость:</span>
                <strong>{{ $trend['velocity_kmh'] ?? '—' }} км/ч</strong>
              </li>
            </ul>
            <div class="mt-3 text-end">
              <a class="btn btn-outline-primary btn-sm" href="/osdr">Перейти к OSDR →</a>
            </div>
          @else
            <div class="text-muted text-center py-4">Нет данных</div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function () {
  if (typeof L !== 'undefined' && typeof Chart !== 'undefined') {
    const last = @json(($last['payload'] ?? []));
    let lat0 = Number(last.latitude || 0), lon0 = Number(last.longitude || 0);
    
    const map = L.map('map', { attributionControl: false }).setView([lat0 || 0, lon0 || 0], lat0 ? 3 : 2);
    L.tileLayer('https://{s}.tile.openstreetmap.de/{z}/{x}/{y}.png', {
      noWrap: true,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const trail = L.polyline([], { color: '#0d6efd', weight: 4, opacity: 0.8 }).addTo(map);
    
    // Возвращена стандартная синяя метка Leaflet (как было изначально)
    const marker = L.marker([lat0 || 0, lon0 || 0]).addTo(map).bindPopup('<b>МКС</b>');

    // Оба графика — чисто синий цвет (#0d6efd)
    const blueColor = '#0d6efd';

    const speedChart = new Chart(document.getElementById('issSpeedChart'), {
      type: 'line',
      data: { labels: [], datasets: [{ label: 'Скорость (км/ч)', data: [], borderColor: blueColor, backgroundColor: blueColor + '20', tension: 0.3, fill: false }] },
      options: { responsive: true, scales: { x: { display: false }, y: { beginAtZero: false } }, plugins: { legend: { display: false } } }
    });

    const altChart = new Chart(document.getElementById('issAltChart'), {
      type: 'line',
      data: { labels: [], datasets: [{ label: 'Высота (км)', data: [], borderColor: blueColor, backgroundColor: blueColor + '20', tension: 0.3, fill: false }] },
      options: { responsive: true, scales: { x: { display: false }, y: { beginAtZero: false } }, plugins: { legend: { display: false } } }
    });

    async function loadTrend() {
      try {
        const r = await fetch('/api/iss/trend?limit=240');
        const js = await r.json();
        const points = js.points || [];
        
        if (points.length > 0) {
          const latlngs = points.map(p => [p.lat, p.lon]);
          trail.setLatLngs(latlngs);
          const lastPos = latlngs[latlngs.length - 1];
          marker.setLatLng(lastPos);
          map.setView(lastPos, 3);
        }

        const times = points.map(p => new Date(p.at).toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' }));
        speedChart.data.labels = times;
        speedChart.data.datasets[0].data = points.map(p => p.velocity);
        speedChart.update('none');

        altChart.data.labels = times;
        altChart.data.datasets[0].data = points.map(p => p.altitude);
        altChart.update('none');
      } catch (e) {
        console.error('Ошибка загрузки тренда:', e);
      }
    }

    loadTrend();
    setInterval(loadTrend, 15000);
  }
});
</script>
@endsection