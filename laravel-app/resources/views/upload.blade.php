<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RIAH.AI - Scanner</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-black text-white flex items-center justify-center h-screen font-sans">

    <div class="bg-gray-900 p-8 rounded-2xl shadow-2xl w-full max-w-md border border-blue-900/50">

        <div class="text-center mb-8">
            <div class="inline-block p-3 rounded-full bg-blue-600/10 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white">
                Riah<span class="text-blue-500">.AI</span>
            </h1>
            <p class="text-gray-400 mt-2">Extração inteligente de Notas Fiscais</p>
        </div>

        @if(session('erro'))
            <div class="bg-red-600/20 border border-red-500 text-red-500 p-3 rounded-lg mb-4 text-sm">
                {{ session('erro') }}
            </div>
        @endif

        @if(session('sucesso'))
            <div class="bg-green-600/20 border border-green-500 text-green-500 p-3 rounded-lg mb-4 text-sm">
                {{ session('sucesso') }}
            </div>
        @endif
        <form action="/enviar" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            <div class="relative">
                <label class="block text-sm font-medium text-gray-300 mb-2">Upload da Imagem</label>
                <input type="file" name="nota_fiscal" accept="image/*,application/pdf" required
                    class="block w-full text-sm text-gray-400
                    file:mr-4 file:py-2.5 file:px-4
                    file:rounded-lg file:border-0
                    file:text-sm file:font-bold
                    file:bg-blue-600 file:text-white
                    hover:file:bg-blue-700
                    file:transition-all file:cursor-pointer
                    bg-gray-800 rounded-lg border border-gray-700 focus:outline-none focus:border-blue-500">
            </div>

            <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 px-4 rounded-xl
                shadow-[0_0_15px_rgba(37,99,235,0.4)] transition-all duration-300 transform hover:scale-[1.02] active:scale-95">
                Analisar Nota Fiscal
            </button>
        </form>
        <a href="/notas" class="block w-full text-center mt-4 text-gray-400 hover:text-white text-sm transition-colors">
            Ver Histórico de Notas →
        </a>

        <div class="mt-8 pt-6 border-t border-gray-800 text-center">
            <span class="text-xs text-gray-500 uppercase tracking-widest font-semibold">Realizado por Riah Sander</span>
        </div>
    </div>

</body>
</html>
