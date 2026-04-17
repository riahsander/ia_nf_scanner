import os
import io
import json
import uvicorn
import pytesseract
from fastapi import FastAPI, UploadFile, File
from groq import Groq
from dotenv import load_dotenv
from PIL import Image
from pdf2image import convert_from_bytes

# 1. CONFIGURAÇÕES
load_dotenv()
API_KEY = os.getenv("GROQ_API_KEY")

if not API_KEY:
    raise ValueError("ERRO: A variável GROQ_API_KEY não foi configurada!")

client = Groq(api_key=API_KEY)

# Mudamos para um modelo focado em texto e raciocínio lógico (Llama 3.3 70B)
MODEL_NAME = "llama-3.3-70b-versatile"

app = FastAPI()

@app.get("/")
async def root():
    return {"status": "IA com Tesseract Online", "model": MODEL_NAME}

@app.post("/extract")
async def extract_data(file: UploadFile = File(...)):
    try:
        file_bytes = await file.read()
        
        # 1. PROCESSAMENTO DA IMAGEM / PDF
        if file.content_type == "application/pdf":
            # Converte PDF para imagem (DPI alto para o Tesseract ler melhor)
            images = convert_from_bytes(file_bytes, dpi=300)
            image_to_ocr = images[0]
        else:
            image_to_ocr = Image.open(io.BytesIO(file_bytes))

        # 2. EXTRAÇÃO DE TEXTO BRUTO (Tesseract)
        # O lang='por' garante que ele reconheça caracteres como R$, Ç e acentos
        texto_extraido = pytesseract.image_to_string(image_to_ocr, lang='por')
        
        print(f"\n>>>> TEXTO CAPTURADO PELO TESSERACT:\n{texto_extraido[:500]}...\n")

        # 3. PROMPT PARA ORGANIZAÇÃO (A IA agora só organiza o texto)
        prompt = (
            "Você é um especialista em OCR. Abaixo está o texto bruto de uma nota fiscal extraído via OCR. "
            "Sua tarefa é organizar esses dados em um JSON puro. "
            "Campos obrigatórios: empresa_emissora, cnpj, data_emissao, valor_total, itens (lista com nome e preco), categoria. "
            "CATEGORIAS: Alimentação, Transporte, Saúde, Educação, Lazer ou Suprimentos. "
            "...Certifique-se de que o JSON seja válido. Se o nome de um item contiver aspas, use aspas simples ou escape-as. Não use quebras de linha dentro dos valores das propriedades."
            f"TEXTO BRUTO:\n{texto_extraido}"
        )

        # 4. CHAMADA GROQ (TEXTO PARA JSON)
        chat_completion = client.chat.completions.create(
            messages=[{"role": "user", "content": prompt}],
            model=MODEL_NAME,
            temperature=0.1,
            response_format={"type": "json_object"}
        )

        raw_response = chat_completion.choices[0].message.content
        dados_objeto = json.loads(raw_response)

        return {"status": "sucesso", "dados": dados_objeto}

    except Exception as e:
        print(f">>>> ERRO NO PROCESSAMENTO: {str(e)}")
        return {"status": "erro", "mensagem": str(e)}

if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=8000)