FROM python:3.11-slim

# 1. Instala todas as dependências do sistema de uma só vez
# Colocamos no início para que o Docker salve isso em cache
RUN apt-get update && apt-get install -y \
    tesseract-ocr \
    tesseract-ocr-por \
    libtesseract-dev \
    poppler-utils \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# 2. Copia apenas o requirements primeiro (otimiza o cache do Docker)
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# 3. Copia o restante do código
COPY . .

# 4. Comando de inicialização
CMD ["python", "-m", "uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8000"]