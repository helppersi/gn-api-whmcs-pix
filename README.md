# gn-api-whmcs-pix


## Instalação

1. Faça o download da última versão do módulo;
2. Descompacte o arquivo baixado;
3. Copie o arquivo gerencianetpix.php e a pasta gerencianetpix, para o diretório /modules/gateways da instalação do WHMCS;
4. Copie o arquivo gerencianetpix.php, disponível no diretório callback/pix, para o diretório modules/gateways/callback. Ele deve seguir o modelo modules/gateways/callback/gerencianetpix.php;
5. Copie o arquivo gerencianet.php, disponível no diretório /hooks, para o diretório includes/hooks. Ele deverá seguir o modelo includes/hooks/gerencianet.php;

Os arquivos do módulo Gerencianet devem seguir a seguinte estrutura no WHMCS:

```
includes/hooks/
  |- gerencianet.php
 modules/gateways/
  |- callback/gerencianetpix.php
  |  gerencianetpix/
  |  gerencianetpix.php
```
6. Crie uma pasta na raiz do seu servidor e insira seu certificado .pem na pasta.

Obs: O passo 6 é opcional, devendo ser seguido apenas se o administrador do WHMCS desejar que as faturas atualizadas no WHMCS também tenham seu status atualizados automaticamente na Gerencianet.

## Configuração do Módulo

![Captura de tela de 2021-03-12 12-44-49](https://user-images.githubusercontent.com/39035667/111999989-e8a8e180-8af3-11eb-8b08-f51d2787c982.png)
1. **Client_Id Produção:** Deve ser preenchido com o client_id de produção de sua conta Gerencianet. Este campo é obrigatório e pode ser encontrado no menu "API" -> "Minhas Aplicações". Em seguida, selecione sua aplicação criada, conforme é mostrado no [link](http://image.prntscr.com/image/7dc272063bb74dccba91739701a0478b.png);
2. **Client_Secret Produção:** Deve ser preenchido com o client_secret de produção de sua conta Gerencianet. Este campo é obrigatório e pode ser encontrado no menu "API" ->  "Minhas Aplicações". Em seguida, selecione sua aplicação criada, conforme é mostrado no [link](http://image.prntscr.com/image/7dc272063bb74dccba91739701a0478b.png);
3. **Client_Id Desenvolvimento:** Deve ser preenchido com o client_id de desenvolvimento de sua conta Gerencianet. Este campo é obrigatório e pode ser encontrado no menu "Nova API" -> "Minhas Aplicações". Em seguida, selecione sua aplicação criada, conforme é mostrado no [link](http://image.prntscr.com/image/447be4bc64644a35bcf5eaecd1125f5d.png);
4. **Client_Secret Desenvolvimento:** Deve ser preenchido com o client_secret de desenvolvimento de sua conta Gerencianet. Este campo é obrigatório e pode ser encontrado no menu "Nova API" -> "Minhas Aplicações". Em seguida, selecione sua aplicação criada, conforme é mostrado no [link](http://image.prntscr.com/image/447be4bc64644a35bcf5eaecd1125f5d.png);
5. **Sandbox:** Caso seja de seu interesse, habilite o ambiente de testes da API Gerencianet;
6. **Debug:** Neste campo é possível habilitar os logs de transação e de erros da Gerencianet no painel WHMCS;
7. **Certificado Pix** Deve ser preenchido com o caminho do certificado salvo em seu servidor;
8. **Desconto:** Informe o valor de desconto que deverá ser aplicado ao pix gerado exclusivamente pela Gerencianet;
9. **Validade da Cobrança** Deve ser informado o período de validade em dias da cobrança PIX;
10. **Mtls** Entenda os riscos de não configurar o mTLS acessando o link https://gnetbr.com/rke4baDVyd.
