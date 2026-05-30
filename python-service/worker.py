import json
import time
import redis
import mysql.connector

# 1. Подключаемся к Redis (хост совпадает с именем сервиса в docker-compose)
r = redis.Redis(host='redis', port=6379, decode_responses=True)
queue_key = 'queues:seo-tasks'

# 2. Функция для подключения к MySQL
def get_db_connection():
    return mysql.connector.connect(
        host="mysql",
        user="root",
        password="root_password",
        database="ca_database"
    )

print("Python SEO-Воркер успешно запущен и слушает Redis...")

# ... ваш существующий код подключения к Redis и MySQL ...

while True:
    try:
        result = r.blpop(queue_key, timeout=0)
        
        if result:
            _, payload_raw = result
            task_data = json.loads(payload_raw)
            
            task_id = task_data['task_id']
            text_to_analyze = task_data['text']
            
            print(f"📦 Задача №{task_id} получена. Начинаю анализ текста...")
            time.sleep(5) # Имитируем тяжелую работу
            
            char_count = len(text_to_analyze)
            word_count = len(text_to_analyze.split())
            analysis_result = f"Слов: {word_count}, Символов: {char_count}"
            
            # Обновляем MySQL
            db = get_db_connection()
            cursor = db.cursor()
            query = "UPDATE seo_tasks SET status = %s, result = %s, updated_at = NOW() WHERE id = %s"
            cursor.execute(query, ("done", analysis_result, task_id))
            db.commit()
            cursor.close()
            db.close()
            print(f"✅ Задача №{task_id} успешно сохранена в MySQL!")
            
            # 🔥 НАШ МОСТ: Публикуем событие для Laravel в Redis Pub/Sub
            notification = json.dumps({"task_id": task_id, "result": analysis_result})
            r.publish('seo-finished', notification)
            print(f"🔔 Сигнал о завершении задачи №{task_id} отправлен в Redis Pub/Sub!")
            
    except Exception as e:
        print(f"❌ Ошибка в цикле воркера: {e}")
        time.sleep(2)
