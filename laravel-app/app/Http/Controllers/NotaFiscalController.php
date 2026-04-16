<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\NotaFiscal; // Importante para salvar no banco

class NotaFiscalController extends Controller
{
    // Função que recebe a imagem, envia para a IA e SALVA
    public function store(Request $request)
    {
        try {
            $file = $request->file('nota_fiscal');

            // Envia para o container Python (ajustado para o nome python_ai do seu docker ps)
            $response = Http::attach(
                'file', file_get_contents($file), $file->getClientOriginalName()
            )->post('http://python_ai:8000/extract');

            $respostaPython = json_decode($response->body(), true);
            $dados = $respostaPython['dados'];

            // SALVA OS DADOS NO BANCO DE DADOS
            NotaFiscal::create([
                'empresa_emissora' => $dados['empresa_emissora'] ?? 'Não identificado',
                'cnpj'             => $dados['cnpj'] ?? null,
                'data_emissao'     => $dados['data'] ?? null,
                'valor_total'      => $dados['valor_total'] ?? 0,
                'categoria'        => $dados['categoria_da_compra'] ?? 'Outros',
                'itens'            => $dados['itens_comprados'] ?? [],
            ]);

            // Após salvar, redireciona para a listagem organizada
            return redirect('/notas')->with('sucesso', 'Nota fiscal analisada com sucesso!');

        } catch (\Exception $e) {
            return response()->json(['erro' => 'Falha na conexão: ' . $e->getMessage()], 500);
        }
    }

    // Busca os dados no banco e exibe na tela organizada
    public function index()
    {
        // Busca todas as notas salvas
        $notas = NotaFiscal::orderBy('created_at', 'desc')->get();

        // Retorna a view organizada (dashboard)
        return view('notas.index', compact('notas'));
    }
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
