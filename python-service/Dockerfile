FROM python:3.11-slim

WORKDIR /app

# Установка системных зависимостей (если понадобятся для сборки некоторых пакетов)
RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    && rm -rf /var/lib/apt/lists/*

# Копируем и устанавливаем зависимости Python
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Копируем остальной код
COPY . .

EXPOSE 8000
