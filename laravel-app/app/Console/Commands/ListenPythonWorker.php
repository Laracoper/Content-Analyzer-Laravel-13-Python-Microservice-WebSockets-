<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Events\SeoTaskFinished;

class ListenPythonWorker extends Command
{
    protected $signature = 'python:listen';
    protected $description = 'Слушает Redis Pub/Sub через чистые TCP сокеты';

    public function handle()
    {
        $this->info('🚀 Слушатель Python-воркера запущен через TCP-мост и ждет событий...');

        // Открываем прямое соединение с контейнером Redis по протоколу TCP
        $socket = fsockopen('redis', 6379, $errno, $errstr, 30);

        if (!$socket) {
            $this->error("❌ Не удалось подключиться к Redis: $errstr ($errno)");
            return 1;
        }

        // Отправляем команду подписки в формате протокола Redis (RESP)
        // Команда: SUBSCRIBE seo-finished
        fwrite($socket, "*2\r\n\$9\r\nSUBSCRIBE\r\n\$12\r\nseo-finished\r\n");

        // Бесконечный цикл чтения ответов из сокета
        while (!feof($socket)) {
            $line = fgets($socket);
            
            // Если прилетело сообщение от Redis Pub/Sub
            if (str_contains($line, 'seo-finished')) {
                // Читаем следующую строку (размер сообщения) и затем саму строку с JSON
                fgets($socket); 
                $messageJson = trim(fgets($socket));
                
                $data = json_decode($messageJson, true);
                
                if (isset($data['task_id'], $data['result'])) {
                    $this->info("🔔 Получен сигнал от Python по задаче №" . $data['task_id']);
                    
                    // Транслируем событие в Laravel Reverb!
                    event(new SeoTaskFinished((int)$data['task_id'], $data['result']));
                }
            }
        }

        fclose($socket);
    }
}
