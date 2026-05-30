<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Microservice App' }}</title>
    <!-- Подключаем Tailwind CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-50 font-sans min-h-screen flex flex-col">

    <!-- Навигационная панель с адаптивным меню на Alpine.js -->
    <nav x-data="{ open: false }" class="bg-indigo-600 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                <!-- Логотип -->
                <div class="flex items-center">
                    <span class="font-bold text-lg tracking-wider">📦 ANALYZER.CORE</span>
                </div>

                <!-- Десктопное меню -->
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="/" class="bg-indigo-700 px-3 py-2 rounded-md text-sm font-medium">Главная</a>
                        <a href="http://localhost:8084/phpmyadmin/" target="_blank" class="hover:bg-indigo-500 px-3 py-2 rounded-md text-sm font-medium">phpMyAdmin</a>
                    </div>
                </div>

                <!-- Кнопка Бургер (Мобильная версия) -->
                <div class="flex md:hidden">
                    <button @click="open = !open" type="button" class="inline-flex items-center justify-center p-2 rounded-md hover:bg-indigo-500 focus:outline-none">
                        <svg class="h-6 w-6" :class="{'hidden': open, 'block': !open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg class="h-6 w-6" :class="{'block': open, 'hidden': !open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Мобильное выпадающее меню -->
        <div x-show="open" x-transition class="md:hidden bg-indigo-700">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="/" class="block bg-indigo-800 px-3 py-2 rounded-md text-base font-medium">Главная</a>
                <a href="http://localhost:8084/phpmyadmin/" target="_blank" class="block hover:bg-indigo-600 px-3 py-2 rounded-md text-base font-medium">phpMyAdmin</a>
            </div>
        </div>
    </nav>

    <!-- Основной контент страницы -->
    <main class="flex-grow container mx-auto px-4 py-8 sm:px-6 lg:px-8">
        {{ $slot }}
    </main>

    <!-- Футер -->
    <footer class="bg-gray-800 text-gray-400 text-center py-4 text-sm mt-auto">
        &copy; {{ date('Y') }} — Laravel 13 + Livewire Volt + Python Microservice
    </footer>

    @livewireScripts
</body>
</html>
