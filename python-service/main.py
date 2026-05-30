from fastapi import FastAPI
from pydantic import BaseModel

app = FastAPI(title="Content Analyzer API")

# Описываем структуру входящего JSON-запроса от Laravel
class TextCheckRequest(BaseModel):
    text: str

# Базовый хелсчек, который мы проверяли через Nginx
@app.get("/api/v1/health")
def health_check():
    return {"status": "ok", "message": "Python API работает стабильно"}

# Эндпоинт для синхронной проверки текста на спам
@app.post("/api/v1/check-spam")
def check_spam(data: TextCheckRequest):
    # Наш "взрослый" черный список стоп-слов
    spam_words = ["купить", "акция", "крипта", "бесплатно", "заработок", "казино"]
    
    text_lower = data.text.lower()
    
    # Ищем совпадения стоп-слов в тексте
    found_words = [word for word in spam_words if word in text_lower]
    is_spam = len(found_words) > 0
    
    return {
        "is_spam": is_spam,
        "reason": f"Найдены стоп-слова: {', '.join(found_words)}" if is_spam else "Текст прошел проверку"
    }
