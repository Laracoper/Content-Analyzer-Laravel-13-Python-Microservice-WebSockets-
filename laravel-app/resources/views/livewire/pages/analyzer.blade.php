<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

new #[Layout('components.layouts.app')] #[Title('Панель анализа текста')] class extends Component {
    public string $text = '';
    public string $spamResult = '';
    public string $seoStatus = 'Ожидает отправки';

    public ?int $currentTaskId = null;
    public string $seoResult = '';

    // Метод, который автоматически возвращает список задач для HTML-шаблона
    public function with(): array
    {
        return [
            'tasks' => DB::table('seo_tasks')->latest()->get(),
        ];
    }

    public function checkSpam()
    {
        $this->validate(['text' => 'required|min:3']);

        try {
            $response = Http::post('http://ca-nginx/api/v1/check-spam', [
                'text' => $this->text,
            ]);

            if ($response->successful()) {
                $isSpam = $response->json('is_spam');
                $this->spamResult = $isSpam ? '🛑 Внимание: Обнаружен спам!' : '✅ Текст успешно прошел проверку!';
            } else {
                $this->spamResult = '❌ Ошибка микросервиса Python';
            }
        } catch (\Exception $e) {
            $this->spamResult = '❌ Не удалось связаться с Python: ' . $e->getMessage();
        }
    }

    public function sendToSeoQueue()
    {
        $this->validate(['text' => 'required|min:3']);

        $taskId = DB::table('seo_tasks')->insertGetId([
            'text' => $this->text,
            'status' => 'processing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->currentTaskId = $taskId;
        $this->seoResult = '';

        $payload = json_encode([
            'task_id' => $taskId,
            'text' => $this->text,
        ]);

        $socket = fsockopen('redis', 6379, $errno, $errstr, 5);
        if ($socket) {
            $redisKey = 'queues:seo-tasks';
            $cmd = "*3\r\n\$5\r\nRPUSH\r\n\$" . strlen($redisKey) . "\r\n" . $redisKey . "\r\n\$" . strlen($payload) . "\r\n" . $payload . "\r\n";
            fwrite($socket, $cmd);
            fclose($socket);

            $this->seoStatus = "🚀 Задача №{$taskId} в очереди. Веб-сокеты Reverb слушают эфир...";
        } else {
            $this->seoStatus = "❌ Ошибка отправки в Redis: $errstr";
        }

        $this->text = '';
    }

    // Ловим сигнал от веб-сокета Reverb
    #[On('echo:seo-analysis,SeoTaskFinished')]
    public function onTaskFinished($event)
    {
        // Лог для проверки в консоли бэкенда (вы увидите его в логах Laravel)
        logger('Сокет прилетел!', $event);
        // 1. Если завершилась наша текущая задача, выводим её в блок фокуса
        if ($this->currentTaskId && (int) $event['taskId'] === $this->currentTaskId) {
            $this->seoStatus = "✅ Задача №{$this->currentTaskId} обработана мгновенно через сокеты!";
            $this->seoResult = $event['result'];
            $this->currentTaskId = null;
        }

        // 2. 🔥 ГЛАВНОЕ ИСПРАВЛЕНИЕ: Принудительно заставляем Livewire обновить HTML на экране
        $this->dispatch('$refresh');
    }

    // Метод для удаления одной конкретной задачи
    public function deleteTask($id)
    {
        DB::table('seo_tasks')->where('id', $id)->delete();

        if ($this->currentTaskId === (int) $id) {
            $this->currentTaskId = null;
            $this->seoStatus = 'Ожидает отправки';
        }
    }

    // Метод для полной очистки истории
    public function clearHistory()
    {
        DB::table('seo_tasks')->truncate();
        $this->currentTaskId = null;
        $this->seoResult = '';
        $this->seoStatus = 'Ожидает отправки';
    }
}; ?>

<div class="max-w-4xl mx-auto mt-10 px-4 space-y-8">

    <!-- Форма отправки -->
    <div class="p-6 bg-white rounded-lg shadow-md">
        <h2 class="text-xl font-bold mb-4 text-gray-800">📦 Content Analyzer (Laravel 13 + Python + Reverb)</h2>

        <div class="mb-4">
            <textarea wire:model="text" placeholder="Введите ваш текст здесь..."
                class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" rows="4"></textarea>
            @error('text')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <div class="flex flex-wrap gap-4 mb-6">
            <button wire:click="checkSpam"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition duration-200">
                Проверить на СПАМ (REST)
            </button>

            <button wire:click="sendToSeoQueue"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md font-medium transition duration-200">
                SEO Анализ (Redis Queue)
            </button>
        </div>

        <div class="border-t pt-4 space-y-2 text-sm">
            <p class="text-gray-700"><strong>Статус REST:</strong> {{ $spamResult ?: 'Не проверялось' }}</p>

            <div class="text-gray-700">
                <strong>Статус Очереди:</strong> {{ $seoStatus }}
                @if ($currentTaskId)
                    <span class="inline-block ml-2 text-xs text-green-500 animate-pulse">(⚡ Reverb WebSockets
                        Live...)</span>
                @endif
            </div>

            @if ($seoResult)
                <div class="mt-4 p-3 bg-indigo-50 border border-indigo-200 rounded-md animate-fade-in">
                    <p class="text-indigo-800 font-semibold">📊 Результат последнего SEO-анализа:</p>
                    <p class="text-gray-600 mt-1">{{ $seoResult }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Блок истории задач -->
    <div class="p-6 bg-white rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-800">📜 История проверок (Всего: {{ count($tasks) }})</h3>
            @if (count($tasks) > 0)
                <button wire:click="clearHistory" wire:confirm="Вы уверены, что хотите полностью очистить историю?"
                    class="text-xs bg-red-100 text-red-700 hover:bg-red-200 px-3 py-1.5 rounded font-medium transition duration-200">
                    🗑️ Очистить историю
                </button>
            @endif
        </div>

        @if (count($tasks) === 0)
            <p class="text-gray-500 text-center py-6 text-sm">История пуста. Отправьте первый текст на анализ!</p>
        @else
            <div class="overflow-x-auto border rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 text-sm text-left">
                    <thead class="bg-gray-50 text-gray-700 uppercase text-xs font-semibold tracking-wider">
                        <tr>
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Текст задания</th>
                            <th class="px-4 py-3">Статус</th>
                            <th class="px-4 py-3">Результат анализа</th>
                            <th class="px-4 py-3 text-right">Действие</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white text-gray-600">
                        @foreach ($tasks as $task)
                            <tr class="hover:bg-gray-50 transition duration-150" wire:key="task-{{ $task->id }}">
                                <td class="px-4 py-3 font-mono text-gray-400">#{{ $task->id }}</td>
                                <td class="px-4 py-3 max-w-xs truncate" title="{{ $task->text }}">
                                    {{ $task->text }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($task->status === 'processing')
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 animate-pulse">
                                            ⚙️ В работе
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                            ✅ Готово
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-800">
                                    {{ $task->result ?: '—' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button wire:click="deleteTask({{ $task->id }})"
                                        class="text-gray-400 hover:text-red-600 p-1 rounded transition duration-150"
                                        title="Удалить задачу">
                                        ❌
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
