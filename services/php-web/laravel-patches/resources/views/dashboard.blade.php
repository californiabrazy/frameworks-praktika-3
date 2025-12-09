@extends('layouts.app')

@section('content')
<div class="container pb-5 py-5">
    <div class="row g-4">

        <!-- 1. Астрономические события -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title m-0">Астрономические события (AstronomyAPI)</h5>
                        <form id="astroForm" class="row g-2 align-items-center">
                            <div class="col-auto">
                                <input type="number" step="0.0001" class="form-control form-control-sm" name="lat" value="55.7558" placeholder="lat" style="width:120px">
                            </div>
                            <div class="col-auto">
                                <input type="number" step="0.0001" class="form-control form-control-sm" name="lon" value="37.6176" placeholder="lon" style="width:120px">
                            </div>
                            <div class="col-auto">
                                <input type="number" min="1" max="30" class="form-control form-control-sm" name="days" value="7" style="width:120px" title="дней">
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-sm btn-primary" type="submit">Показать</button>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Тело</th>
                                    <th>Событие</th>
                                    <th>Время события</th>
                                    <th>Дополнительная информация</th>
                                </tr>
                            </thead>
                            <tbody id="astroBody">
                                <tr><td colspan="5" class="text-muted text-center">Загрузка...</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <details class="mt-3">
                        <summary class="text-muted small">Показать полный JSON</summary>
                        <pre id="astroRaw" class="bg-light rounded p-3 small mt-2" style="max-height: 400px; overflow: auto; white-space: pre-wrap;"></pre>
                    </details>
                </div>
            </div>
        </div>

        <!-- 2. JWST — последние изображения (галерея) -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title m-0">JWST — последние изображения</h5>
                        <form id="jwstFilter" class="row g-2 align-items-center">
                            <div class="col-auto">
                                <select class="form-select form-select-sm" name="source" id="srcSel" style="width:120px">
                                    <option value="jpg" selected>Все JPG</option>
                                    <option value="suffix">По суффиксу</option>
                                    <option value="program">По программе</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <select class="form-select form-select-sm" name="suffix" id="suffixInp" style="width:120px;display:none">
                                    <option value="_cal">_cal</option>
                                    <option value="_thumb">_thumb</option>
                                </select>
                                <input type="text" class="form-control form-control-sm" name="program" id="progInp" placeholder="2734" style="width:120px;display:none">
                            </div>
                            <div class="col-auto">
                                <select class="form-select form-select-sm" name="instrument" style="width:120px">
                                    <option value="">Любой</option>
                                    <option>NIRCam</option><option>MIRI</option><option>NIRISS</option><option>NIRSpec</option><option>FGS</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <select class="form-select form-select-sm" name="perPage" style="width:120px">
                                    <option>12</option><option selected>24</option><option>36</option><option>48</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-sm btn-primary" type="submit">Показать</button>
                            </div>
                        </form>
                    </div>

                    <div class="position-relative">
                        <button id="scrollLeft" class="btn btn-light border shadow-sm position-absolute top-50 start-0 translate-middle-y z-10" style="left: 10px;" type="button">‹</button>
                        <div id="jwstTrack" class="d-flex gap-3 overflow-auto py-2 px-4" style="scrollbar-width: thin; scroll-snap-type: x mandatory;"></div>
                        <button id="scrollRight" class="btn btn-light border shadow-sm position-absolute top-50 end-0 translate-middle-y z-10" style="right: 10px;" type="button">›</button>
                    </div>

                    <div id="jwstInfo" class="small text-muted mt-2"></div>

                    <style>
                        #jwstTrack::-webkit-scrollbar { height: 6px; }
                        #jwstTrack::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 3px; }
                        #jwstTrack::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 3px; }
                        .jwst-item { flex: 0 0 200px; scroll-snap-align: start; cursor: pointer; }
                        .jwst-item img { width: 100%; height: 200px; object-fit: cover; border-radius: .65rem; transition: .25s; }
                        .jwst-item img:hover { transform: scale(1.08); box-shadow: 0 10px 25px rgba(0,0,0,0.25); }
                        .jwst-cap { font-size: .82rem; margin-top: .4rem; color: #444; text-align: center; }
                    </style>
                </div>
            </div>
        </div>

        <!-- 3. JWST — выбранное наблюдение (изначально скрыт) -->
        <div class="mt-0">
            <div id="jwstDetailCard" class="card shadow-sm h-100 d-none position-relative">
                <button type="button" id="closeDetail" class="btn-close position-absolute top-0 end-0 m-3 z-10" aria-label="Закрыть"></button>
                <div class="card-body" id="detailContent">
                    <div class="text-center py-5 text-muted">Нажмите на изображение в галерее выше</div>
                </div>
            </div>
        </div>

        <!-- 4. Телеметрия CSV -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Данные телеметрии (CSV данные)</h5>
                    @if(!empty($csv_data) && count($csv_data) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>Записано</th>
                                        <th>Напряжение</th>
                                        <th>Температура</th>
                                        <th>Действительность</th>
                                        <th>Файл</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($csv_data as $row)
                                        <tr>
                                            <td>{{ $row['recorded_at'] }}</td>
                                            <td>{{ $row['voltage'] }}</td>
                                            <td>{{ $row['temp'] }}</td>
                                            <td>{{ $row['is_valid'] ? 'Да' : 'Нет' }}</td>
                                            <td>
                                                <a href="{{ url('/download/csv/' . $row['source_file']) }}" target="_blank">
                                                    {{ \Illuminate\Support\Str::limit($row['source_file'], 40) }}
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-muted">Нет данных телеметрии</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- CMS блоки -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-0 position-relative">
                        <a class="d-block text-decoration-none text-dark fw-semibold stretched-link"
                           data-bs-toggle="collapse" href="#cmsContent" role="button"
                           aria-expanded="false" aria-controls="cmsContent"
                           style="cursor: pointer; padding: 0.75rem 0; margin: -0.75rem 0;">
                            Информационные блоки
                        </a>
                    </h5>

                    <div class="collapse mt-3" id="cmsContent">
                        <div class="pt-3 border-top">
                            @if(empty($cms_blocks) || !count($cms_blocks))
                                <p class="text-muted mb-0">Нет добавленных блоков</p>
                            @else
                                @foreach($cms_blocks as $block)
                                    <div class="mb-4 {{ !$loop->last ? 'pb-4 border-bottom' : '' }}">
                                        @if(!empty($block['title']))
                                            <h5 class="text-primary mb-3 mt-4">{{ $block['title'] }}</h5>
                                        @endif
                                        <div class="prose prose-sm">
                                            {!! $block['content'] !!}
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    #jwstDetailCard {
        transition: opacity 0.35s ease;
        opacity: 0;
    }
    #jwstDetailCard:not(.d-none) {
        opacity: 1;
    }
</style>

<script>
// === Астрономические события (без изменений) ===
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('astroForm');
    const body = document.getElementById('astroBody');
    const raw  = document.getElementById('astroRaw');

    async function load(q){
        body.innerHTML = '<tr><td colspan="5" class="text-muted text-center">Загрузка…</td></tr>';
        const url = '/api/astro/events?' + new URLSearchParams(q).toString();
        try {
            const r  = await fetch(url);
            const js = await r.json();
            raw.textContent = JSON.stringify(js, null, 2);

            const rows = [];
            if(js.data && Array.isArray(js.data.rows)){
                js.data.rows.forEach(rw => {
                    const bodyName = rw.body?.name || rw.body?.id || '—';
                    // Словарь переводов небесных тел (можно расширять)
                    const bodyTranslations = {
                        'Sun': 'Солнце',
                        'Moon': 'Луна',
                        'Mercury': 'Меркурий',
                        'Venus': 'Венера',
                        'Mars': 'Марс',
                        'Jupiter': 'Юпитер',
                        'Saturn': 'Сатурн',
                        'Uranus': 'Уран',
                        'Neptune': 'Нептун',
                        'Pluto': 'Плутон',
                        // Добавь другие, если нужно
                    };

                    // Функция для перевода имени тела
                    function translateBody(name) {
                        if (!name) return '—';
                        return bodyTranslations[name] || name; // если нет в словаре — оставляем как есть
                    }

                    if (Array.isArray(rw.events) && rw.events.length) {
                        rw.events.forEach(ev => {
                            const rise = ev.rise ? new Date(ev.rise).toLocaleString('ru-RU', {
                                timeZone: 'UTC',
                                year: 'numeric',
                                month: '2-digit',
                                day: '2-digit',
                                hour: '2-digit',
                                minute: '2-digit',
                                second: '2-digit'
                            }) : '—';

                            const set = ev.set ? new Date(ev.set).toLocaleString('ru-RU', {
                                timeZone: 'UTC',
                                year: 'numeric',
                                month: '2-digit',
                                day: '2-digit',
                                hour: '2-digit',
                                minute: '2-digit',
                                second: '2-digit'
                            }) : '—';

                            // Переведённое имя тела
                            const translatedName = translateBody(bodyName);

                            // Тип события
                            const eventType = ev.type === 'Rise' ? 'Восход' :
                                            ev.type === 'Set'  ? 'Заход'  :
                                            ev.type === 'Culmination' ? 'Кульминация' :
                                            ev.type || '—';

                            // Формируем время красиво и без красного цвета
                            let timeHtml = '';
                            if (ev.rise && ev.set) {
                                timeHtml = `Восход: <strong>${rise}</strong><br>Заход: <strong>${set}</strong>`;
                            } else if (ev.rise) {
                                timeHtml = `<strong>${rise}</strong>`;
                            } else if (ev.set) {
                                timeHtml = `<strong>${set}</strong>`;
                            } else {
                                timeHtml = '—';
                            }

                            // Доп. инфа
                            const extra = ev.extraInfo
                                ? Object.entries(ev.extraInfo)
                                    .map(([k, v]) => {
                                        if (k === 'obscuration') return `Затмение: ${v}`;
                                        if (k === 'elongation') return `Элонгация: ${v}°`;
                                        if (k === 'phase') return `Фаза: ${v}`;
                                        return `${k}: ${v}`;
                                    })
                                    .join('; ')
                                : '—';

                            rows.push({
                                name: translatedName,
                                type: eventType,
                                when: timeHtml,
                                extra: extra
                            });
                        });
                    }
                });
            }

            if(!rows.length){
                body.innerHTML = '<tr><td colspan="5" class="text-muted text-center">События не найдены</td></tr>';
                return;
            }

            body.innerHTML = rows.map((r,i) => `
                <tr>
                    <td>${i+1}</td>
                    <td>${r.name}</td>
                    <td>${r.type}</td>
                    <td>${r.when}</td>
                    <td>${r.extra}</td>
                </tr>
            `).join('');

        } catch(e){
            body.innerHTML = '<tr><td colspan="5" class="text-danger text-center">Ошибка загрузки</td></tr>';
        }
    }

    form.addEventListener('submit', ev => {
        ev.preventDefault();
        const q = Object.fromEntries(new FormData(form).entries());
        load(q);
    });

    load({lat: 55.7558, lon: 37.6176, days: 7});
});

// === JWST галерея + детальная карточка ===
document.addEventListener('DOMContentLoaded', function () {
    const track         = document.getElementById('jwstTrack');
    const info          = document.getElementById('jwstInfo');
    const detailCard    = document.getElementById('jwstDetailCard');
    const detailContent = document.getElementById('detailContent');
    const closeBtn      = document.getElementById('closeDetail');
    const srcSel        = document.getElementById('srcSel');
    const suffixInp     = document.getElementById('suffixInp');
    const progInp       = document.getElementById('progInp');

    function toggleInputs() {
        const val = srcSel.value;
        suffixInp.style.display = val === 'suffix' ? 'block' : 'none';
        progInp.style.display = val === 'program' ? 'block' : 'none';
    }

    srcSel.addEventListener('change', toggleInputs);
    toggleInputs(); // initial call

    closeBtn.addEventListener('click', () => {
        detailCard.style.opacity = '0';
        setTimeout(() => detailCard.classList.add('d-none'), 350);
    });

    async function loadFeed(q) {
        track.innerHTML = '<div class="text-center py-5 text-muted">Загрузка...</div>';
        try {
            const res = await fetch('/api/jwst/feed?' + new URLSearchParams(q));
            const data = await res.json();

            track.innerHTML = '';
            info.textContent = `Показано: ${data.items?.length || 0}`;

            (data.items || []).forEach(item => {
                const div = document.createElement('div');
                div.className = 'jwst-item';

                // Короткая подпись — только программа и наблюдение
                const shortCaption = item.program && item.obs 
                    ? `JW${String(item.program).padStart(5, '0')} • ${item.obs}`
                    : 'JWST';

                div.innerHTML = `
                    <a href="#" class="text-decoration-none d-block">
                        <div class="position-relative overflow-hidden rounded shadow-sm bg-dark">
                            <img loading="lazy"
                                 src="${item.url}"
                                 class="w-100"
                                 style="height: 200px; object-fit: cover; transition: transform .3s;"
                                 alt="JWST">
                            <div class="position-absolute bottom-0 start-0 end-0 bg-gradient-to-t from-black/70 to-transparent p-2">
                                <div class="text-white small fw-medium text-shadow">
                                    ${shortCaption}
                                </div>
                            </div>
                        </div>
                    </a>
                `;

                const a = div.querySelector('a');
                // Сохраняем ВСЁ в data-атрибутах
                a.dataset.url      = item.url;
                a.dataset.caption  = (item.caption || '').trim() || 'Без описания';
                a.dataset.program  = item.program || '';
                a.dataset.obs      = item.obs || '';
                a.dataset.suffix   = item.suffix || '';
                a.dataset.inst     = (item.inst || []).join(', ');

                // Ховер-эффект
                const img = div.querySelector('img');
                div.addEventListener('mouseenter', () => img.style.transform = 'scale(1.08)');
                div.addEventListener('mouseleave', () => img.style.transform = 'scale(1)');

                track.appendChild(div);
            });
        } catch (e) {
            track.innerHTML = '<div class="text-danger p-4">Ошибка загрузки</div>';
            console.error(e);
        }
    }

    // Клик — показываем ВСЮ информацию только здесь
    track.addEventListener('click', function(e) {
        const a = e.target.closest('a');
        if (!a) return;
        e.preventDefault();

        const url     = a.dataset.url;
        const caption = a.dataset.caption;
        const program = a.dataset.program;
        const obs     = a.dataset.obs;
        const inst    = a.dataset.inst;
        const suffix  = a.dataset.suffix;

        // Извлекаем фильтр из суффикса (например _f200w)
        const filter = suffix.match(/_(f\d+w)/i)?.[1].toUpperCase() || '';

        detailContent.innerHTML = `
            <h5 class="card-title mb-4">JWST — выбранное наблюдение</h5>
            
            <div class="text-center mb-4">
                <img src="${url}" class="img-fluid rounded shadow" style="max-height: 68vh; max-width: 100%;">
            </div>

            <div class="small text-muted lh-lg">
                ${program ? `<strong>Программа:</strong> JW${String(program).padStart(5, '0')}<br>` : ''}
                ${obs ? `<strong>Наблюдение:</strong> ${obs}<br>` : ''}
                ${inst ? `<strong>Инструмент:</strong> ${inst}<br>` : ''}
                ${filter ? `<strong>Фильтр:</strong> ${filter}<br>` : ''}
                <strong>Файл:</strong> <code class="text-break small">${url.split('/').pop()}</code>
            </div>

            ${caption !== 'Без описания' ? `
                <hr class="my-4">
                <div class="small">
                    <strong>Описание:</strong>
                    <div class="p-3 bg-light rounded border mt-2 small">${caption.replace(/\n/g, '<br>')}</div>
                </div>
            ` : ''}

            <div class="mt-4 text-center">
                <a href="${url}" target="_blank" class="btn btn-primary btn-sm">Скачать изображение</a>
            </div>
        `;

        detailCard.classList.remove('d-none');
        setTimeout(() => detailCard.style.opacity = '1', 50);
        detailCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });

    // Фильтры и прокрутка
    document.getElementById('jwstFilter')?.addEventListener('submit', e => {
        e.preventDefault();
        loadFeed(Object.fromEntries(new FormData(e.target)));
    });

    document.getElementById('scrollLeft')?.addEventListener('click', () => track.scrollBy({left: -600, behavior: 'smooth'}));
    document.getElementById('scrollRight')?.addEventListener('click', () => track.scrollBy({left: 600, behavior: 'smooth'}));

    loadFeed({ source: 'jpg', perPage: 24 });
});
</script>
@endsection