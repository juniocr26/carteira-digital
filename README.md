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

### 3. Gerar uma chave SSH

Dentro do container, gere sua chave SSH com:

```bash
ssh-keygen -t rsa
```

* Aperte **Enter** três vezes para aceitar os padrões e gerar a chave.

Depois, visualize a chave pública gerada:

```bash
cat /root/.ssh/id_rsa.pub
```

Copie o conteúdo exibido e adicione na sua conta do GitHub em **Settings → SSH and GPG keys → New SSH key**.

### 4. Instalar dependências do projeto

Ainda dentro do container, rode:

```bash
composer install
```

Na primeira execução, será solicitado confirmar a **fingerprint**. Digite:

```bash
yes
```

Pronto! O ambiente da **Carteira Criptografia** está configurado e pronto para uso. Agora você pode começar a desenvolver ou testar transações utilizando a Stripe.

## Estrutura do Projeto

* `docker/` → Dockerfiles e configuração dos containers
* `src/` → Código-fonte da aplicação
* `.env` → Variáveis de ambiente do projeto
* `docker-compose.yml` → Orquestração dos containers

## Requisitos

* Docker e Docker Compose instalados
* Conta no GitHub para adicionar a chave SSH
* Conexão com a internet para baixar dependências
