<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\NotaFiscal;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class NotaFiscalController extends Controller
{
    /**
     * 1. Processa o upload, envia para a IA e salva no banco.
     */
    public function store(Request $request)
    {
        if (!$request->hasFile('nota_fiscal')) {
            return back()->with('erro', 'Nenhuma imagem selecionada.');
        }

        try {
            $request->validate([
                'nota_fiscal' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240'
            ]);

            $file = $request->file('nota_fiscal');
            $urlIA = env('PYTHON_AI_URL', 'http://python-ai:8000') . '/extract';

            // Envia para o container Python
            $response = Http::timeout(120)->attach(
                'file',
                file_get_contents($request->file('documento')->getRealPath()),
                'nota.jpg'
            )->post('https://api-scanner-python.onrender.com/extract');

            if ($response->failed()) {
                return back()->with('erro', 'A IA falhou. Verifique se o container python-ai está rodando.');
            }

            $respostaRaw = $response->json();
            $dados = $respostaRaw['dados'] ?? [];

            // --- 1. TRATAMENTO DO VALOR TOTAL ---
            $valorTotal = $dados['valor_total'] ?? 0;
            if (!is_numeric($valorTotal)) {
                $valorTotal = preg_replace('/[^0-9,.]/', '', $valorTotal);
                $valorTotal = str_replace('.', '', $valorTotal);
                $valorTotal = str_replace(',', '.', $valorTotal);
            }

            // --- 2. TRATAMENTO DA DATA (Evita erro de formato 16/04/2026) ---
            $dataRaw = $dados['data_emissao'] ?? now()->format('Y-m-d');
            $dataFormatada = null;
            try {
                if (str_contains($dataRaw, '/')) {
                    $dataFormatada = Carbon::createFromFormat('d/m/Y', $dataRaw)->format('Y-m-d');
                } else {
                    $dataFormatada = Carbon::parse($dataRaw)->format('Y-m-d');
                }
            } catch (\Exception $e) {
                $dataFormatada = now()->format('Y-m-d');
            }

            // --- 3. BLINDAGEM DOS ITENS (Evita SyntaxError no Modal) ---
            $itens = is_array($dados['itens'] ?? null) ? $dados['itens'] : [];

            // Troca aspas duplas por simples e remove quebras de linha que quebram o JSON
            array_walk_recursive($itens, function(&$valor) {
                if (is_string($valor)) {
                    $valor = str_replace(['"', "\\", "\n", "\r"], ["'", "/", " ", ""], $valor);
                }
            });

            // --- 4. PERSISTÊNCIA NO BANCO ---
            NotaFiscal::create([
                'empresa_emissora' => $dados['empresa_emissora'] ?? 'Não identificado',
                'cnpj'             => $dados['cnpj'] ?? 'N/A',
                'data_emissao'     => $dataFormatada,
                'valor_total'      => floatval($valorTotal),
                'categoria'        => $dados['categoria'] ?? 'Outros',
                'itens'            => $itens,
            ]);

            return redirect()->route('notas.index')->with('sucesso', 'Nota fiscal analisada e salva com sucesso!');

        } catch (\Exception $e) {
            Log::error("Erro no Processamento: " . $e->getMessage());
            return back()->with('erro', 'Falha técnica: ' . $e->getMessage());
        }
    }

    /**
     * 2. Lista todas as notas no histórico.
     */
    public function index()
    {
        $notas = NotaFiscal::orderBy('created_at', 'desc')->get();
        return view('notas.index', compact('notas'));
    }

    /**
     * 3. Retorna os dados de uma nota específica (Usado pelo Modal/Olhinho).
     */
    public function show($id)
{
    try {
        $nota = NotaFiscal::findOrFail($id);

        // Se por algum motivo o banco retornar string em vez de array, o Laravel corrige aqui
        if (is_string($nota->itens)) {
            $nota->itens = json_decode($nota->itens, true);
        }

        return response()->json($nota, 200, [], JSON_UNESCAPED_UNICODE);
    } catch (\Exception $e) {
        return response()->json(['erro' => $e->getMessage()], 500);
    }
}

    /**
     * 4. Exclui um registro.
     */
    public function destroy($id)
    {
        try {
            $nota = NotaFiscal::findOrFail($id);
            $nota->delete();
            return redirect('/notas')->with('sucesso', 'Registro apagado com sucesso!');
        } catch (\Exception $e) {
            return redirect('/notas')->with('erro', 'Não foi possível apagar o registro.');
        }
    }
}
