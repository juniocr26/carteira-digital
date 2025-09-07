# Carteira Criptografia

O **Carteira Criptografia** é um projeto que permite realizar transações eletrônicas utilizando a Stripe. Ele foi desenvolvido para ser facilmente configurável via Docker, garantindo que todos os ambientes necessários estejam isolados e prontos para uso.

## Funcionalidades

* Processamento de pagamentos com Stripe
* Gerenciamento seguro de chaves SSH
* Integração com RabbitMQ e MySQL
* Ambiente totalmente containerizado com Docker

## Instalação

Siga os passos abaixo para instalar e configurar o projeto:

### 1. Subir os containers

No diretório do projeto, execute:

```bash
docker compose up -d --build
```

Isso irá construir e iniciar todos os containers necessários (app, webserver, banco de dados e RabbitMQ).

### 2. Entrar no container da aplicação

Para acessar o container principal da aplicação, rode:

```bash
docker exec -it carteira-digital-app bash
```

### 3. Migração das tabelas

Ainda dentro do container, rode:

```bash
php artisan migrate
```

## Estrutura do Projeto

* `docker/` → Dockerfiles e configuração dos containers
* `src/` → Código-fonte da aplicação
* `.env` → Variáveis de ambiente do projeto
* `docker-compose.yml` → Orquestração dos containers

## Requisitos

* Docker e Docker Compose instalados
* Conta no GitHub para adicionar a chave SSH
* Conexão com a internet para baixar dependências

## Problema: Public Key Retrieval

Se você estiver utilizando MySQL 8 ou superior, pode ocorrer o erro:

```
Public Key Retrieval is not allowed
```

Isso acontece porque o MySQL exige autenticação segura e o driver JDBC não está autorizado a buscar a chave pública do servidor.

### Como resolver

1. **Permitir a recuperação da chave pública**
   No arquivo de configuração da conexão com o banco de dados (`.env` ou configuração do Laravel), adicione:

   ```env
   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=carteira-digital
   DB_USERNAME=root
   DB_PASSWORD=secret
   DB_ALLOW_PUBLIC_KEY_RETRIEVAL=true
   DB_USE_SSL=false
   ```

   Ou, se estiver usando o **JDBC URL** diretamente, inclua os parâmetros:

   ```
   jdbc:mysql://host:3306/banco?allowPublicKeyRetrieval=true&useSSL=false
   ```

2. **Alternativa (alterar o plugin de autenticação do usuário)**
   No MySQL, você pode alterar o plugin de autenticação para `mysql_native_password`:

   ```sql
   ALTER USER 'usuario'@'%' IDENTIFIED WITH mysql_native_password BY 'sua_senha';
   FLUSH PRIVILEGES;
   ```

3. **Usar SSL** (opcional e recomendado)
   Se quiser manter a conexão segura, configure o SSL no Laravel/DBeaver e defina `DB_USE_SSL=true`.

Após essas alterações, reinicie a aplicação ou container para que a conexão com o MySQL funcione corretamente.

## 💳 Cartões de Teste da Stripe

A Stripe fornece cartões fictícios para simular pagamentos no ambiente de **teste**.  
Eles **não funcionam no mundo real** e devem ser usados apenas em sandbox.

### Como usar
- **Validade** → qualquer data futura (ex: `12/34`)  
- **CVC** → qualquer número de 3 dígitos (ex: `123`)  
- **CEP** → qualquer CEP válido (ex: `12345`)  

### Cartão principal (sempre aprovado)
```
4242 4242 4242 4242
```

### Exemplos de cenários de teste

| Número do cartão        | Cenário simulado              |
|--------------------------|-------------------------------|
| `4000 0000 0000 9995`   | Saldo insuficiente            |
| `4000 0000 0000 0002`   | Cartão recusado (genérico)    |
| `4000 0000 0000 9987`   | Cartão perdido                |
| `4000 0000 0000 9979`   | Cartão roubado                |
| `4000 0000 0000 0069`   | Cartão expirado               |
| `4000 0027 6000 3184`   | Requer autenticação 3D Secure |

👉 Esses cartões permitem validar fluxos de pagamento, falhas e autenticação sem precisar de cartões reais.
