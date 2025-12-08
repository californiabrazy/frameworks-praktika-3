<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Space Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

    <style>
        body { background-color: #ffffff; }
        #map { height: 340px; }

        main {
            opacity: 0;
            animation: fadeInPage 0.6s ease-out forwards;
        }

        @keyframes fadeInPage {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-white">

<!-- Навигация -->
<nav class="navbar navbar-expand-lg fixed-top bg-white shadow-sm border-bottom">
    <div class="container">

        <!-- Кнопка бургера для мобильных -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Меню слева, крупный шрифт, жирные ссылки -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav fs-5 fw-semibold gap-4">
                <li class="nav-item">
                    <a class="nav-link text-dark {{ request()->is('dashboard') ? 'text-primary' : '' }}"
                       href="/dashboard">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-dark {{ request()->is('iss*') ? 'text-primary' : '' }}"
                       href="/iss">ISS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-dark {{ request()->is('osdr*') ? 'text-primary' : '' }}"
                       href="/osdr">OSDR</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Основной контент -->
<main class="container py-5 pt-lg-4">
    @yield('content')
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>