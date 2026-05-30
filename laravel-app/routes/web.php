<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Монтируем компонент analyzer на главную страницу по-взрослому
Volt::route('/', 'pages.analyzer');