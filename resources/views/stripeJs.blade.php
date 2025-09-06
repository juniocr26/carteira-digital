<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compra com Cartão</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://js.stripe.com/v3/"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

    <!-- Header -->
    <header class="bg-white shadow">
        <div class="max-w-2xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Compra com Cartão</h2>
        </div>
    </header>

    <!-- Main -->
    <main class="flex-1 py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">

                <!-- Aviso de processamento -->
                <div id="warning-message" class="hidden mb-4 p-3 rounded bg-yellow-100 border border-yellow-300 text-yellow-800 text-sm">
                    O pagamento está sendo processado, você será notificado quando houver uma atualização.
                </div>
                <form id="payment-form" class="space-y-6">

                    <!-- Nome do titular -->
                    <div>
                        <label for="cardholder-name" class="block text-sm font-medium text-gray-700">
                            Nome no Cartão
                        </label>
                        <input type="text" id="cardholder-name"
                            class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Nome do titular" required>
                    </div>

                    <!-- CPF -->
                    <div>
                        <label for="cpf" class="block text-sm font-medium text-gray-700">
                            CPF
                        </label>
                        <input type="text" id="cpf"
                            class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="000.000.000-00" maxlength="14" required>
                    </div>

                    <!-- Valor da compra -->
                    <div>
                        <label for="valor-compra" class="block text-sm font-medium text-gray-700">
                            Valor da Compra (R$)
                        </label>
                        <input type="number" id="valor-compra" min="0.01" step="0.01"
                            class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Ex: 150.50" required>
                    </div>

                    <!-- Campo do cartão -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cartão de Crédito</label>
                        <div id="card-element" class="mt-1 p-2 border rounded-md shadow-sm"></div>
                        <div id="card-errors" class="text-red-500 mt-2 text-sm" role="alert"></div>
                    </div>

                    <!-- Botão -->
                    <div class="flex justify-end">
                        <button id="card-button" type="submit"
                                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                            Pagar
                        </button>
                    </div>
                    <!-- GIF de carregamento -->
                    <div id="loading" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                        <img src="{{ asset('loading.gif') }}" alt="Carregando..." class="w-24 h-24">
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="https://js.pusher.com/8.2/pusher.min.js"></script>
    <script>
        // Stripe
        /*
            Exemplos de cartões de teste Stripe (modo sandbox):

            1️⃣ Pagamento aprovado (Visa)
            Número: 4242 4242 4242 4242

            2️⃣ Pagamento recusado (cartão inválido)
            Número: 4000 0000 0000 9995

            3️⃣ Mastercard aprovado
            Número: 5555 5555 5555 4444

            4️⃣ Outros testes úteis:
            - Fundos insuficientes: 4000 0000 0000 9999
            - 3D Secure obrigatório: 4000 0025 0000 3155
        */
        const stripe = Stripe("{{ env('STRIPE_PUBLIC') }}");
        const elements = stripe.elements();
        const cardElement = elements.create('card', { hidePostalCode: true });
        const loading = document.getElementById('loading');
        cardElement.mount('#card-element');
        // Configura Pusher
        Pusher.logToConsole = true; // opcional, para debug
        const pusher = new Pusher("{{ env('PUSHER_APP_KEY') }}", {
            cluster: "{{ env('PUSHER_APP_CLUSTER') }}",
            wsHost: "{{ env('PUSHER_HOST', '127.0.0.1') }}",
            wsPort: {{ env('PUSHER_PORT', 6001) }},
            forceTLS: false,
            disableStats: true,
            encrypted: false
        });

        // Máscara CPF
        const cpfInput = document.getElementById('cpf');
        cpfInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, "").slice(0,11);
            value = value.replace(/(\d{3})(\d)/, "$1.$2");
            value = value.replace(/(\d{3})(\d)/, "$1.$2");
            value = value.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
            e.target.value = value;
        });

        const form = document.getElementById('payment-form');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Mostra a GIF
            loading.classList.remove('hidden');

            const cardholderName = document.getElementById('cardholder-name').value;
            const cpf = document.getElementById('cpf').value.replace(/\D/g, "");
            const valorCompra = parseFloat(document.getElementById('valor-compra').value);

            if (isNaN(valorCompra) || valorCompra <= 0) {
                Swal.fire({ icon: 'error', title: 'Erro', text: 'Informe um valor válido para a compra' });
                return;
            }

            const { paymentMethod, error } = await stripe.createPaymentMethod({
                type: 'card',
                card: cardElement,
                billing_details: { name: cardholderName }
            });

            if (error) {
                Swal.fire({ icon: 'error', title: 'Erro', text: error.message });
            } else {
                try {
                    const response = await fetch("{{ route('stripe.tokenizar') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            payment_method_id: paymentMethod.id,
                            valor_compra: valorCompra,
                            cpf: cpf,
                            nome: cardholderName,
                            descricao_transacao: `Compra de saldo para ${cardholderName} CPF: ${cpf}`,
                            tipo_transacao: '1'
                        })
                    });

                    const data = await response.json();

                    switch (data.status) {
                        case 'sucesso':
                            loading.classList.add('hidden');
                            Swal.fire({ icon: 'success', title: 'Sucesso', text: data.message });
                            break;

                        case 'warning':
                            document.getElementById('warning-message').classList.remove('hidden');
                            const transactionId = data.content;

                            // Agora escuta pelo canal público
                            const channel = pusher.subscribe(`private-${transactionId}`);
                                channel.bind('App\\Events\\PagamentoProcessado', function(e) {
                                    document.getElementById('warning-message').classList.add('hidden');
                                    loading.classList.add('hidden');

                                    if (e.status === 'sucesso') {
                                        Swal.fire({ icon: 'success', title: 'Sucesso', text: e.message });
                                    } else {
                                        Swal.fire({ icon: 'error', title: 'Erro', text: e.message });
                                    }
                                });
                            break;

                        case 'erro':
                            loading.classList.add('hidden');
                            Swal.fire({ icon: 'error', title: 'Erro', text: data.message || 'Tente novamente' });
                            break;

                        default:
                            loading.classList.add('hidden');
                            Swal.fire({ icon: 'error', title: 'Erro', text: data.message || 'Tente novamente' });
                            break;
                    }

                } catch (err) {
                    loading.classList.add('hidden');
                    Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro ao processar pagamento' });
                }
            }
        });
    </script>
</body>
</html>
