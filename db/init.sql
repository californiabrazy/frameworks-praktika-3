-- ===== Basic schema =====

CREATE TABLE IF NOT EXISTS iss_fetch_log (
    id BIGSERIAL PRIMARY KEY,
    fetched_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    source_url TEXT NOT NULL,
    payload JSONB NOT NULL
);

CREATE TABLE IF NOT EXISTS telemetry_legacy (
    id BIGSERIAL PRIMARY KEY,
    recorded_at TIMESTAMPTZ NOT NULL,
    voltage NUMERIC(6,2) NOT NULL,
    temp NUMERIC(6,2) NOT NULL,
    source_file TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS cms_pages (
    id BIGSERIAL PRIMARY KEY,
    slug TEXT UNIQUE NOT NULL,
    title TEXT NOT NULL,
    body TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS cms_blocks (
    id BIGSERIAL PRIMARY KEY,
    slug TEXT NOT NULL,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

INSERT INTO cms_pages(slug, title, body)
VALUES
('welcome', 'Добро пожаловать', '<h3>Демо контент</h3><p>Этот текст хранится в БД</p>'),
('unsafe', 'Небезопасный пример', '<script>console.log("XSS training")</script><p>Защита работает, лога в консоли нет.</p>')
ON CONFLICT DO NOTHING;

INSERT INTO cms_blocks (slug, title, content, is_active)
VALUES 
('dashboard_experiment', 'Актуальная новость',
 '<p>Сегодня JWST передал серию снимков галактики NGC 5068. Галерея обновлена!</p>',
 TRUE),
('dashboard_experiment', 'Комментарий по МКС',
 '<p>МКС движется со скоростью около 28 000 км/ч. Графики справа показывают изменения скорости и высоты за последние 4 часа.</p>',
 TRUE),
('dashboard_experiment', 'Космическая справка',
 '<p>Интересный факт: Свет от Солнца до Земли идёт примерно 8 минут 20 секунд.</p>',
 TRUE);
