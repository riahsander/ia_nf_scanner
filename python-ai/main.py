import os
import io
import base64
import uvicorn
from fastapi import FastAPI, UploadFile, File
from groq import Groq
from dotenv import load_dotenv
from PIL import Image
from pdf2image import convert_from_bytes
import json

load_dotenv()

# Inicializa o cliente Groq
client = Groq(api_key=os.getenv("GROQ_API_KEY"))
MODEL_NAME = "meta-llama/llama-4-scout-17b-16e-instruct"

app = FastAPI()

def encode_image(image_bytes):
    """Codifica os bytes da imagem em base64 para a API do Groq."""
    return base64.b64encode(image_bytes).decode('utf-8')

@app.get("/")
async def root():
    return {"status": "IA Groq Online", "model": MODEL_NAME}

@app.post("/extract")
async def extract_data(file: UploadFile = File(...)):
    try:
        file_bytes = await file.read()
        
        # 1. Tratamento de PDF
        if file.content_type == "application/pdf":
            images = convert_from_bytes(file_bytes)
            img_byte_arr = io.BytesIO()
            images[0].save(img_byte_arr, format='JPEG')
            image_to_process = img_byte_arr.getvalue()
        else:
            image_to_process = file_bytes

        base64_image = encode_image(image_to_process)

        # 2. Prompt com as categorias desejadas
        prompt = (
            "Analise esta nota fiscal e extraia as informações. "
            "Retorne APENAS um JSON puro, sem formatação markdown, com exatamente estes campos: "
            "empresa_emissora, cnpj, data, valor_total, itens_comprados (lista com nome e preco) "
            "e categoria_da_compra (identifique se é alimentação, transporte, saúde, educação, "
            "lazer ou suprimentos, baseando-se nos itens da nota)."
        )

        # 3. Chamada à API da Groq
        chat_completion = client.chat.completions.create(
            messages=[
                {
                    "role": "user",
                    "content": [
                        {"type": "text", "text": prompt},
                        {
                            "type": "image_url",
                            "image_url": {
                                "url": f"data:image/jpeg;base64,{base64_image}",
                            },
                        },
                    ],
                }
            ],
            model=MODEL_NAME,
            temperature=0.1 
        )

        # 4. Limpeza e Conversão (A MÁGICA ACONTECE AQUI)
        raw_response = chat_completion.choices[0].message.content
        clean_json_string = raw_response.replace("```json", "").replace("```", "").strip()

        # Tenta transformar a string devolvida pela IA num objeto JSON real do Python
        try:
            dados_objeto = json.loads(clean_json_string)
        except json.JSONDecodeError:
            # Proteção caso a IA devolva algo que não seja um JSON perfeito
            dados_objeto = {"aviso": "Erro de formatação da IA", "conteudo": clean_json_string}

        # 5. Retorno
        # O FastAPI vai automaticamente formatar 'dados_objeto' de forma correta sem escapar os acentos
        return {
            "status": "sucesso",
            "dados": dados_objeto
        }

    except Exception as e:
        return {"status": "erro", "mensagem": str(e)}

if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=8000)