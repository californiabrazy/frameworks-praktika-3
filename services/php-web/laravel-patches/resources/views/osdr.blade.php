{{-- resources/views/osdr.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-5">
    <h3 class="text-center mb-4">NASA OSDR</h3>

    <div class="card shadow">
        <div class="card-body p-4">

            <!-- Форма поиска -->
            <form method="GET" class="mb-4">
                <div class="row g-3 justify-content-start">
                    <div class="col-lg-3 col-md-8">
                        <input type="text"
                               name="search"
                               class="form-control"
                               placeholder="Поиск по названию датасета..."
                               value="{{ request('search') }}"
                               autofocus>
                    </div>
                    <div class="col-lg-1 col-md-4">
                        <button type="submit" class="btn btn-primary w-100">Найти</button>
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
                    <thead class="">
                        <tr>
                            <th width="60" class="text-center">#</th>
                            <th width="120">
                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'dataset_id', 'sort_order' => ($sort_by === 'dataset_id' && $sort_order === 'asc' ? 'desc' : 'asc')]) }}" class="text-decoration-none text-dark">
                                    ID датасета
                                </a>
                            </th>
                            <th width="120">
                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'title', 'sort_order' => ($sort_by === 'title' && $sort_order === 'asc' ? 'desc' : 'asc')]) }}" class="text-decoration-none text-dark">
                                    Название датасета
                                </a>
                            </th>
                            <th width="110">
                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'inserted_at', 'sort_order' => ($sort_by === 'inserted_at' && $sort_order === 'asc' ? 'desc' : 'asc')]) }}" class="text-decoration-none text-dark">
                                    Время добавления
                                </a>
                            </th>
                            <th width="70" class="text-center">JSON</th>
                            <th width="200">RAW</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $row)
                            @php
                                $search = trim(request('search'));
                                $title  = $row['title'] ?? '';
                                $datasetId = $row['dataset_id'] ?? '';

                                $isMatch = $search && (
                                    strcasecmp(trim($title), trim($search)) === 0 ||
                                    strcasecmp(trim($datasetId), trim($search)) === 0
                                )
                            @endphp

                            <tr class="{{ $isMatch ? 'table-primary bg-primary-subtle' : '' }}">
                                <td class="text-center text-muted small">{{ $loop->iteration + $items->firstItem() - 1 }}</td>
                                <td class="font-monospace small text-muted">
                                    {{ Str::limit($datasetId, 18) }}
                                </td>
                                <td style="max-width: 560px;">
                                    <div class="text-truncate" title="{{ $title }}">
                                        @if($search && $title)
                                            {!! preg_replace(
                                                '/^' . preg_quote($search, '/') . '$/i',
                                                '<mark class="bg-primary-subtle">$0</mark>',
                                                e($title)
                                            ) !!}
                                        @else
                                            {{ $title ?: '—' }}
                                        @endif
                                    </div>
                                </td>
                                <td class="small text-muted">{{ $row['inserted_at'] ? \Carbon\Carbon::parse($row['inserted_at'])->format('d.m.Y H:i:s') : '—' }}</td>
                                <td class="text-center">
                                    @if(!empty($row['rest_url']))
                                        <a href="{{ $row['rest_url'] }}" target="_blank" rel="noopener"
                                        class="btn btn-outline-secondary btn-sm">JSON</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="small text-muted">
                                    <pre class="mb-0">{{ json_encode($row['raw'] ?? [], JSON_PRETTY_PRINT) }}</pre>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
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

            <!-- Минималистичная пагинация: только Previous / Next + инфо о количестве -->
            @if ($items->hasPages() || $items->total() > 0)
                <nav class="mt-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <!-- Showing X to Y of Z results -->
                        <div class="text-muted small">
                            @if($items->total() === 0)
                                Нет записей
                            @else
                                Показаны с
                                <strong>{{ $items->firstItem() }}</strong>
                                по
                                <strong>{{ $items->lastItem() }}</strong>
                                из
                                <strong>{{ $items->total() }}</strong>
                                результатов
                            @endif
                        </div>

                        <!-- Previous / Next -->
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item {{ $items->onFirstPage() ? 'disabled' : '' }}">
                                <a class="page-link"
                                   href="{{ $items->previousPageUrl() }}"
                                   @if($items->onFirstPage()) tabindex="-1" aria-disabled="true" @endif
                                   rel="prev">
                                    Предыдущая
                                </a>
                            </li>

                            <li class="page-item {{ !$items->hasMorePages() ? 'disabled' : '' }}">
                                <a class="page-link"
                                   href="{{ $items->nextPageUrl() }}"
                                   @if(!$items->hasMorePages()) tabindex="-1" aria-disabled="true" @endif
                                   rel="next">
                                    Следующая
                                </a>
                            </li>
                        </ul>
                    </div>
                </nav>
            @endif

        </div>
    </div>
</div>
@endsection