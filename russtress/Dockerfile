# Используйте официальный образ Python как базовый
FROM python:3.8


ENV PIP_CACHE_DIR=/pip_cache

# Установите рабочую директорию в контейнере
WORKDIR /app

# Скопируйте файлы зависимостей в рабочую директорию
COPY requirements.txt requirements.txt

RUN mkdir /pip_cache
RUN touch /pip_cache/test

# Установите зависимости
RUN pip install --cache-dir /pip_cache -r requirements.txt




# Скопируйте исходный код вашего приложения в контейнер
COPY . .


# Укажите команду для запуска приложения
CMD ["python", "./app.py"]
