import os
import io
import json
import logging
import uvicorn
import pytesseract
from fastapi import FastAPI, UploadFile, File, HTTPException
from groq import Groq
from dotenv import load_dotenv
from PIL import Image
from pdf2image import convert_from_bytes

# =============================================================================
# 1. CONFIGURAÇÕES
# =============================================================================

load_dotenv()

# Logging estruturado em vez de print() — tem timestamp, nível e controle de ambiente
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
)
logger = logging.getLogger(__name__)

# Validação da chave na inicialização — falha rápido e claro
API_KEY = os.getenv("GROQ_API_KEY")
if not API_KEY:
    raise ValueError("ERRO FATAL: A variável GROQ_API_KEY não foi configurada!")

# Model configurável via .env — sem necessidade de redeploy para trocar
MODEL_NAME = os.getenv("GROQ_MODEL", "llama-3.3-70b-versatile")

# Timeout na chamada ao Groq — evita container travado indefinidamente
client = Groq(api_key=API_KEY, timeout=60.0)

# Constantes de validação
TIPOS_PERMITIDOS = {"image/jpeg", "image/png", "application/pdf"}
MAX_SIZE_BYTES   = 10 * 1024 * 1024  # 10MB

app = FastAPI(title="Riah.AI — OCR Service", version="1.0.0")

# =============================================================================
# 2. ENDPOINTS
# =============================================================================

@app.get("/")
async def root():
    return {"status": "online", "model": MODEL_NAME}

# Endpoint dedicado de health check para o Render monitorar o serviço
@app.get("/health")
async def health():
    return {"status": "ok"}

@app.post("/extract")
async def extract_data(file: UploadFile = File(...)):

    # --- VALIDAÇÃO DO TIPO DE ARQUIVO ---
    if file.content_type not in TIPOS_PERMITIDOS:
        raise HTTPException(
            status_code=415,
            detail=f"Tipo de arquivo não suportado: {file.content_type}. Use JPG, PNG ou PDF."
        )

    file_bytes = await file.read()

    # --- VALIDAÇÃO DO TAMANHO ---
    if len(file_bytes) > MAX_SIZE_BYTES:
        raise HTTPException(
            status_code=413,
            detail="Arquivo muito grande. O limite é 10MB."
        )

    try:
        # --- 1. PROCESSAMENTO DA IMAGEM / PDF ---
        if file.content_type == "application/pdf":
            logger.info(f"Processando PDF: {file.filename}")
            try:
                images = convert_from_bytes(file_bytes, dpi=300)
                textos = [pytesseract.image_to_string(img, lang='por') for img in images]
                texto_extraido = "\n".join(textos)
            except Exception as e:
                logger.error(f"Erro ao processar PDF: {e}", exc_info=True)
                raise HTTPException(
                    status_code=422,
                    detail="Não foi possível processar o PDF. Verifique se o arquivo não está corrompido."
                )
        else:
            logger.info(f"Processando imagem: {file.filename}")

            # CORREÇÃO: Tenta abrir como imagem — se falhar, tenta como PDF (fallback)
            # Resolve o PIL.UnidentifiedImageError quando o content_type está incorreto
            image_to_ocr = None

            try:
                # Tentativa 1: abre normalmente como imagem
                img_buffer   = io.BytesIO(file_bytes)
                image_to_ocr = Image.open(img_buffer)
                image_to_ocr.load()  # força carregamento completo para validar
            except Exception as img_err:
                logger.warning(f"Falha ao abrir como imagem ({img_err}), tentando como PDF...")

                # Tentativa 2: talvez seja um PDF com content_type errado
                try:
                    images         = convert_from_bytes(file_bytes, dpi=300)
                    textos         = [pytesseract.image_to_string(img, lang='por') for img in images]
                    texto_extraido = "\n".join(textos)
                    image_to_ocr   = None  # sinaliza que já extraiu o texto via PDF
                except Exception as pdf_err:
                    logger.error(f"Falha no fallback PDF: {pdf_err}", exc_info=True)
                    raise HTTPException(
                        status_code=422,
                        detail="Arquivo inválido ou corrompido. Use uma imagem JPG/PNG nítida ou um PDF válido."
                    )

            # Se abriu como imagem com sucesso, extrai o texto agora
            if image_to_ocr is not None:
                texto_extraido = pytesseract.image_to_string(image_to_ocr, lang='por')

        # Log dos primeiros 300 chars — não polui log com dados sensíveis
        logger.info(f"Texto OCR extraído ({len(texto_extraido)} chars): {texto_extraido[:300]}...")

        if not texto_extraido.strip():
            raise HTTPException(
                status_code=422,
                detail="Não foi possível extrair texto da imagem. Verifique a qualidade e nitidez do arquivo."
            )

        # --- 2. PROMPT PARA ORGANIZAÇÃO ---
        prompt = (
            "Você é um especialista em leitura de notas fiscais brasileiras. "
            "Abaixo está o texto bruto extraído via OCR de uma nota fiscal. "
            "Organize os dados em um JSON puro e válido com exatamente estes campos:\n\n"
            "- empresa_emissora (string): nome da empresa que emitiu a nota\n"
            "- cnpj (string): CNPJ no formato 00.000.000/0000-00\n"
            "- data_emissao (string): data no formato DD/MM/AAAA\n"
            "- valor_total (number): valor total da nota em formato numérico (ex: 123.45)\n"
            "- categoria (string): uma de: Alimentação, Transporte, Saúde, Educação, Lazer, Suprimentos\n"
            "- itens (array): lista de objetos com 'nome' (string) e 'preco' (string com 2 casas decimais)\n\n"
            "REGRAS OBRIGATÓRIAS:\n"
            "1. NUNCA omita nenhum dos campos listados acima.\n"
            "2. NUNCA invente dados que não estejam no texto.\n"
            "3. Se um campo não for identificado, use os seguintes valores padrão:\n"
            "   empresa_emissora='Não identificado', cnpj='N/A', data_emissao=null,\n"
            "   valor_total=0, itens=[], categoria='Outros'\n"
            "4. Não use quebras de linha dentro dos valores de string.\n"
            "5. Retorne APENAS o JSON, sem explicações, sem markdown, sem blocos de código.\n\n"
            f"TEXTO BRUTO DA NOTA FISCAL:\n{texto_extraido}"
        )

        # --- 3. CHAMADA GROQ ---
        logger.info("Enviando texto para o Groq...")
        chat_completion = client.chat.completions.create(
            messages=[{"role": "user", "content": prompt}],
            model=MODEL_NAME,
            temperature=0.1,
            response_format={"type": "json_object"}
        )

        raw_response = chat_completion.choices[0].message.content
        logger.info(f"Resposta bruta do Groq: {raw_response[:300]}...")

        # --- 4. PARSE DO JSON ---
        try:
            raw_clean    = raw_response.strip()
            raw_clean    = raw_clean.removeprefix("```json").removeprefix("```")
            raw_clean    = raw_clean.removesuffix("```").strip()
            dados_objeto = json.loads(raw_clean)
        except json.JSONDecodeError:
            logger.error(f"JSON inválido recebido do Groq: {raw_response}", exc_info=True)
            raise HTTPException(
                status_code=502,
                detail="O serviço de IA retornou uma resposta inválida. Tente novamente."
            )

        logger.info("Extração concluída com sucesso.")
        return {"status": "sucesso", "dados": dados_objeto}

    except HTTPException:
        raise

    except Exception as e:
        logger.error(f"Erro inesperado no processamento: {e}", exc_info=True)
        raise HTTPException(
            status_code=500,
            detail="Erro interno no processamento. Tente novamente em instantes."
        )

# =============================================================================
# 3. INICIALIZAÇÃO
# =============================================================================

if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=8000)