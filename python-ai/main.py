import os
import io
import base64
import uvicorn
import json
from fastapi import FastAPI, UploadFile, File
from groq import Groq
from dotenv import load_dotenv
from PIL import Image
from pdf2image import convert_from_bytes

# 1. CARREGAMENTO DE CONFIGURAÇÕES
# load_dotenv() tenta carregar o arquivo .env (útil para rodar localmente)
load_dotenv()

# Prioriza a variável de ambiente do sistema (configurada no painel da Railway)
API_KEY = os.getenv("GROQ_API_KEY")

if not API_KEY:
    # Se não houver chave, o app nem inicia, evitando erros misteriosos depois
    raise ValueError("ERRO: A variável GROQ_API_KEY não foi configurada no ambiente!")

# Inicializa o cliente Groq
client = Groq(api_key=API_KEY)

# Modelo recomendado para visão (Vision)
MODEL_NAME = "llama-3.2-11b-vision-preview" 

app = FastAPI()

def encode_image(image_bytes):
    """Codifica os bytes da imagem em base64 para a API do Groq."""
    return base64.b64encode(image_bytes).decode('utf-8')

@app.get("/")
async def root():
    return {
        "status": "IA Groq Online", 
        "ambiente": "Produção",
        "model": MODEL_NAME
    }

@app.post("/extract")
async def extract_data(file: UploadFile = File(...)):
    try:
        file_bytes = await file.read()
        
        # 1. TRATAMENTO DE PDF (Converte a primeira página para imagem)
        if file.content_type == "application/pdf":
            # Nota: Certifique-se que o poppler-utils esteja instalado no Docker/Servidor
            images = convert_from_bytes(file_bytes)
            img_byte_arr = io.BytesIO()
            images[0].save(img_byte_arr, format='JPEG')
            image_to_process = img_byte_arr.getvalue()
        else:
            image_to_process = file_bytes

        base64_image = encode_image(image_to_process)

        # 2. PROMPT OTIMIZADO PARA JSON PURO
        prompt = (
            "Analise esta imagem de nota fiscal e extraia os dados. "
            "Retorne APENAS um objeto JSON puro, sem explicações, com estes campos exatamente: "
            "empresa_emissora, cnpj, data, valor_total, itens (lista com nome e preco) "
            "e categoria (alimentação, transporte, saúde, educação, lazer ou suprimentos)."
        )

        # 3. CHAMADA À API DA GROQ
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
            temperature=0.1,
            response_format={"type": "json_object"} # Garante que a IA tente enviar JSON
        )

        # 4. LIMPEZA E CONVERSÃO
        raw_response = chat_completion.choices[0].message.content
        
        # Remove possíveis blocos de código markdown que a IA possa enviar
        clean_json_string = raw_response.replace("```json", "").replace("```", "").strip()

        try:
            dados_objeto = json.loads(clean_json_string)
        except json.JSONDecodeError:
            # Caso a IA falhe na estrutura, enviamos o texto bruto para depuração
            dados_objeto = {"erro": "Falha ao processar JSON", "bruto": clean_json_string}

        return {
            "status": "sucesso",
            "dados": dados_objeto
        }

    except Exception as e:
        return {"status": "erro", "mensagem": str(e)}

# 5. CONFIGURAÇÃO DE PORTA DINÂMICA (Essencial para Produção)
if __name__ == "__main__":
    # A Railway e outras plataformas injetam a variável 'PORT'
    port = int(os.getenv("PORT", 8000))
    uvicorn.run(app, host="0.0.0.0", port=port)