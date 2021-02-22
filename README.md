# Extensão Integrai para Woocommerce
Módulo para integrar sua loja com a Integrai, integrando com diversos parceiros com apenas 1 plugin.

## Requisitos

- [Wordpress](https://br.wordpress.org/download/)
- [WooCommerce](https://woocommerce.com/)
- [PHP](http://php.net) >= 5.6.x
- Cron

## Instalação

### Manual
1. Baixe a ultima versão [aqui](https://codeload.github.com/integrai/woocommerce/zip/master)
2. Criar a seguinte estrutura de pastas `wp-content/plugins/integrai` na raiz da sua instalação.
3. Descompacte o arquivo baixado e copie as pastas para dentro do diretório criado acima.

### Instalar usando o [composer](https://getcomposer.org/)
1. Entre na pasta raíz da sua instalação
2. Digite o seguinte comando:
```bash
composer require integrai/woocommerce
```

### Instalar usando o WordPress
1. Acesse o painel administrativo da sua loja.
2. Vá em `Plugins > Adicionar novo`.
3. Busque por `Integrai`.
4. Clique em `Instalar agora`.
5. Na tela `Plugins > Plugins instalados` certifique que o plugin `Integrai` esteja ativado, caso não clique em `Ativar`.

## Configuração
1. Acesse o painel administrativo da sua loja
2. Vá em `Woocommerce > Configurações > Integrações > Integrai`
3. Informe sua **API Key** e sua **Secret Key**, que são informadas [aqui](https://manage.integrai.com.br/settings/account)
4. Salve as configurações
