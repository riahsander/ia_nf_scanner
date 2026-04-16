<th class="p-4 text-center">Ações</th>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Notas Fiscais</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-black text-white p-8 font-sans">

    <div class="max-w-5xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold">Riah<span class="text-blue-500">.AI</span> - Histórico</h1>
            <a href="/" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg font-bold transition">
                + Escanear Nova Nota
            </a>
        </div>

        @if(session('sucesso'))
            <div class="bg-green-900/50 border border-green-500 text-green-300 p-4 rounded-lg mb-6">
                {{ session('sucesso') }}
            </div>
        @endif

        <div class="bg-gray-900 rounded-xl border border-gray-800 overflow-hidden shadow-2xl">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-800 text-gray-400 text-sm uppercase">
                        <th class="p-4">Empresa</th>
                        <th class="p-4">CNPJ</th>
                        <th class="p-4">Data</th>
                        <th class="p-4">Categoria</th>
                        <th class="p-4">Valor Total</th>
                        <th class="p-4 text-center">Itens</th>
                        <th class="p-4 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    @forelse($notas as $nota)
                        <tr class="hover:bg-gray-800/50 transition">
                            <td class="p-4 font-semibold">{{ $nota->empresa_emissora }}</td>
                            <td class="p-4 text-gray-400">{{ $nota->cnpj ?? 'N/A' }}</td>
                            <td class="p-4 text-gray-400">{{ $nota->data_emissao ?? 'N/A' }}</td>
                            <td class="p-4">
                                <span class="bg-blue-900/50 text-blue-300 px-3 py-1 rounded-full text-xs border border-blue-800">
                                    {{ ucfirst($nota->categoria) }}
                                </span>

                            </td>
                            <td class="p-4 font-bold text-green-400">R$ {{ number_format($nota->valor_total, 2, ',', '.') }}</td>
                            <td class="p-4 text-center">
    <button onclick="abrirModal('{{ json_encode($nota->itens) }}', '{{ $nota->empresa_emissora }}')"
            class="text-blue-500 hover:text-blue-400 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        </svg>
    </button>
</td>
                            <td class="p-4 text-center">
                                <form action="{{ route('notas.destroy', $nota->id) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja apagar este registro?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-gray-500 hover:text-red-500 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-gray-500">
                                Nenhuma nota fiscal processada ainda. Faça o upload da primeira!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
<div id="modalItens" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-50">
    <div class="bg-gray-900 border border-gray-800 p-6 rounded-2xl w-full max-w-lg shadow-2xl">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-white">Itens: <span id="modalTitulo" class="text-blue-500"></span></h2>
            <button onclick="fecharModal()" class="text-gray-400 hover:text-white text-2xl">&times;</button>
        </div>

        <div class="max-h-96 overflow-y-auto">
            <table class="w-full text-left">
                <thead class="text-gray-500 text-xs uppercase border-b border-gray-800">
                    <tr>
                        <th class="pb-2">Produto</th>
                        <th class="pb-2 text-right">Preço</th>
                    </tr>
                </thead>
                <tbody id="listaItens" class="text-gray-300 divide-y divide-gray-800">
                    </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function abrirModal(itensJson, empresa) {
        const itens = JSON.parse(itensJson);
        const lista = document.getElementById('listaItens');
        const titulo = document.getElementById('modalTitulo');

        titulo.innerText = empresa;
        lista.innerHTML = '';

        if (itens && itens.length > 0) {
            itens.forEach(item => {
                lista.innerHTML += `
                    <tr>
                        <td class="py-3 font-medium">${item.nome}</td>
                        <td class="py-3 text-right text-green-400 font-bold">R$ ${parseFloat(item.preco).toFixed(2).replace('.', ',')}</td>
                    </tr>`;
            });
        } else {
            lista.innerHTML = '<tr><td colspan="2" class="py-4 text-center text-gray-500">Nenhum item detalhado encontrado.</td></tr>';
        }

        document.getElementById('modalItens').classList.remove('hidden');
    }

    function fecharModal() {
        document.getElementById('modalItens').classList.add('hidden');
    }

    // Fechar ao clicar fora do modal
    window.onclick = function(event) {
        const modal = document.getElementById('modalItens');
        if (event.target == modal) fecharModal();
    }
</script>
</body>
</html>
