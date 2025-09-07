<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compra com Pix</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://js.stripe.com/v3/"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

    <!-- Header -->
    <header class="bg-white shadow">
        <div class="max-w-2xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Compra com Pix</h2>
        </div>
    </header>

    <!-- Main -->
    <main class="flex-1 py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">

                <form id="payment-form" class="space-y-6">
                    <!-- Nome -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Nome</label>
                        <input type="text" id="name"
                            class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Nome completo" required>
                    </div>

                    <!-- CPF -->
                    <div>
                        <label for="cpf" class="block text-sm font-medium text-gray-700">CPF</label>
                        <input type="text" id="cpf"
                            class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="000.000.000-00" maxlength="14" required>
                    </div>

                    <!-- Valor -->
                    <div>
                        <label for="valor-compra" class="block text-sm font-medium text-gray-700">
                            Valor da Compra (R$)
                        </label>
                        <input type="number" id="valor-compra" min="0.01" step="0.01"
                            class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Ex: 150.50" required>
                    </div>

                    <!-- Botão -->
                    <div class="flex justify-end">
                        <button id="pix-button" type="submit"
                                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                            Gerar Pix
                        </button>
                    </div>
                </form>

                <!-- Resultado PIX -->
                <div id="pix-container" class="hidden mt-6 text-center">
                    <h3 class="text-lg font-semibold mb-2">Pague com Pix</h3>
                    <p class="text-sm text-gray-600">Escaneie o QR Code abaixo ou copie o código Pix.</p>

                    <div id="qr-code" class="my-4"></div>
                    <textarea id="pix-code" readonly
                        class="w-full p-2 border rounded bg-gray-50 text-xs"></textarea>
                </div>

            </div>
        </div>
    </main>

    <script>
        const stripe = Stripe("{{ env('STRIPE_PUBLIC') }}");

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

            const name = document.getElementById('name').value;
            const cpf = document.getElementById('cpf').value.replace(/\D/g, "");
            const valorCompra = parseFloat(document.getElementById('valor-compra').value);

            if (isNaN(valorCompra) || valorCompra <= 0) {
                Swal.fire({ icon: 'error', title: 'Erro', text: 'Informe um valor válido' });
                return;
            }

            try {
                const response = await fetch("{{ route('stripe.tokenizar') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        valor_compra: valorCompra,
                        cpf: cpf,
                        descricao_transacao: `Compra realizada por pix no CPF: ${cpf} no valor de ${valorCompra}`,
                        nome: name,
                        tipo_transacao: '4'
                    })
                });

                const data = await response.json();
                console.log(data);

                if (data.content.client_secret) {
                    const result = await stripe.confirmPixPayment(data.content.client_secret, {
                        payment_method: {
                            billing_details: { name: name }
                        }
                    });

                    if (result.error) {
                        Swal.fire({ icon: 'error', title: 'Erro', text: result.error.message });
                    } else {
                        // Mostrar QR code e copiar código Pix
                        document.getElementById('pix-container').classList.remove('hidden');

                        // Se backend já retorna a string do QR, pode exibir aqui também
                        if (result.paymentIntent.next_action && result.paymentIntent.next_action.pix_display_qr_code) {
                            const qrCodeData = result.paymentIntent.next_action.pix_display_qr_code.data;
                            const qrCodeUrl = result.paymentIntent.next_action.pix_display_qr_code.image_url_png;

                            document.getElementById('qr-code').innerHTML = `<img src="${qrCodeUrl}" class="mx-auto">`;
                            document.getElementById('pix-code').value = qrCodeData;
                        }

                        Swal.fire({ icon: 'info', title: 'Pix Gerado', text: 'Finalize o pagamento no seu app bancário.' });
                    }
                } else {
                    Swal.fire({ icon: 'error', title: 'Erro', text: data.message || 'Tente novamente' });
                }
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Erro', text: 'Falha ao processar Pix' });
            }
        });
    </script>
</body>
</html>
