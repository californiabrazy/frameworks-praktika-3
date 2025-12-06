@extends('layouts.app')

@section('content')
<div class="container py-5">
  <!-- Заголовок по центру -->
  <h3 class="text-center mb-4">NASA OSDR</h3>

  <div class="card shadow">
    <div class="card-body p-4">

      <!-- Форма поиска -->
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
            <button type="submit" class="btn btn-primary w-85">Найти</button>
          </div>
          @if(request('search'))
            <div class="col-lg-2 col-md-4">
              <a href="{{ request()->url() }}" class="btn btn-outline-secondary w-100">Очистить</a>
            </div>
          @endif
        </div>
      </form>

      <!-- Таблица -->
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th width="60" class="text-center">#</th>
              <th width="60">ID датасета</th>
              <th width="120">Название датасета</th>
              <th width="110">Время обновления</th>
              <th width="110">Время добавления</th>
              <th width="100" class="text-center">JSON</th>
              <th width="200">RAW</th>
            </tr>
          </thead>
          <tbody>
            @forelse($items as $row)
              <tr>
                <td class="text-center text-muted small">{{ $row['id'] ?? '—' }}</td>
                <td class="font-monospace small text-muted">{{ Str::limit($row['dataset_id'] ?? '—', 18) }}</td>
                <td style="max-width: 560px;">
                  <div class="text-truncate" title="{{ $row['title'] ?? '' }}">
                    {{ $row['title'] ?? '—' }}
                  </div>
                </td>
                <td class="small text-muted">{{ $row['updated_at'] ?? '—' }}</td>
                <td class="small text-muted">{{ $row['inserted_at'] ?? '—' }}</td>
                <td class="text-center">
                  @if(!empty($row['rest_url']))
                    <a href="{{ $row['rest_url'] }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">
                      JSON
                    </a>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td class="small text-muted" style="max-width: 200px;">
                  <div class="text-truncate" title="{{ json_encode($row['raw']) }}">
                    {{ json_encode($row['raw']) }}
                  </div>
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

      <!-- Пагинация -->
      @if(is_object($items) && method_exists($items, 'links'))
        <div class="mt-4 d-flex justify-content-center">
          {{ $items->appends(['search' => request('search')])->links() }}
        </div>
      @endif

    </div>
  </div>
</div>
@endsection
