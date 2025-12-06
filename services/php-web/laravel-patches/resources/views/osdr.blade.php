@extends('layouts.app')

@section('content')
<div class="container py-5">
  <!-- Заголовок по центру, как на странице МКС -->
  <h3 class="text-center mb-4">NASA OSDR</h3>

  <div class="card shadow">
    <div class="card-body p-4">

      <!-- Поиск — нормальный размер (не огромный) -->
      <form method="GET" class="mb-4">
        <div class="row g-3 justify-content-start">
          <div class="col-lg-4 col-md-8">
            <input type="text"
                   name="search"
                   class="form-control"
                   placeholder="Поиск по названию датасета..."
                   value="{{ request('search') }}"
                   autofocus>
          </div>
          <div class="col-lg-2 col-md-4">
            <button type="submit" class="btn btn-primary w-100">Найти</button>
          </div>
          @if(request('search'))
            <div class="col-lg-2 col-md-4">
              <a href="{{ request()->url() }}" class="btn btn-outline-secondary w-100">Очистить</a>
            </div>
          @endif
        </div>
      </form>

      <!-- Таблица — единый чистый стиль для всех строк -->
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th width="60" class="text-center">#</th>
              <th width="140">Dataset ID</th>
              <th>Название датасета</th>
              <th width="100">REST URL</th>
              <th width="110">Updated</th>
              <th width="110">Inserted</th>
              <th width="80" class="text-center">JSON</th>
            </tr>
          </thead>
          <tbody>
            @forelse($items as $row)
              <tr>
                <td class="text-center text-muted small fw-medium">{{ $loop->iteration }}</td>
                <td class="font-monospace small text-muted">
                  {{ Str::limit($row['dataset_id'] ?? '—', 18) }}
                </td>
                <td style="max-width: 560px;">
                  <div class="text-truncate" title="{{ $row['title'] ?? '' }}">
                    {{ $row['title'] ?? '—' }}
                  </div>
                </td>
                <td>
                  @if(!empty($row['rest_url']))
                    <a href="{{ $row['rest_url'] }}" target="_blank" rel="noopener"
                       class="btn btn-outline-primary btn-sm px-3">
                      открыть
                    </a>
                  @else
                    <span class="text-muted small">—</span>
                  @endif
                </td>
                <td class="small text-muted">{{ $row['updated_at'] ?? '—' }}</td>
                <td class="small text-muted">{{ $row['inserted_at'] ?? '—' }}</td>
                <td class="text-center">
                  <button class="btn btn-outline-secondary btn-sm"
                          data-bs-toggle="collapse"
                          data-bs-target="#raw-{{ $row['id'] }}-{{ md5($row['dataset_id'] ?? (string)$row['id']) }}">
                    JSON
                  </button>
                </td>
              </tr>

              <!-- Раскрывающийся JSON — такой же, как был у тебя изначально и всегда работал -->
              <tr class="collapse bg-light" id="raw-{{ $row['id'] }}-{{ md5($row['dataset_id'] ?? (string)$row['id']) }}">
                <td colspan="7" class="p-0 border-0">
                  <pre class="mb-0 p-3 rounded-bottom" style="max-height: 360px; overflow: auto; font-size: 0.84rem; background: #f8f9fa; border: none;">
{{ json_encode($row['raw'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-5">
                  @if(request('search'))
                    Ничего не найдено по запросу «{{ request('search') }}»
                  @else
                    Нет данных
                  @endif
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <!-- Пагинация — безопасная, без ошибок -->
      @if(is_object($items) && method_exists($items, 'links'))
        <div class="mt-4 d-flex justify-content-center">
          {{ $items->appends(['search' => request('search')])->links() }}
        </div>
      @endif

    </div>
  </div>
</div>
@endsection