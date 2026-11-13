## Pix USP

Biblioteca que abstrai as chamadas da API REST/JSON para métodos no contexto de cobranças pix na USP

Para usá-la em seu projeto PHP:

    composer require uspdev/wspix

Dependências mínimas do PHP:

    apt-get install php php-curl

Métodos disponíveis: 

 - gerar($data): recebe um array com os dados para geração do pix (veja exemplo abaixo) e devolve outro com uma chave boleana *status* indicando se o pix foi gerado e uma chave *value* que contém o array completo
 - situacao($codigoIDPix): obtém situação do pix (ativo, pago, etc.) forçando a verificação ativa
 - obter($codigoIDPix): recebe uma string com id do pix e devolve um array com os dados do pix, incluindo o código do Copia e Cola (QR Code) e a imagem do QR Code em base64 
 
## Exemplo de utilização:

Para testá-los, adicione em seu arquivo PHP:

    <?php

    namespace Meu\Lindo\App;
    require_once __DIR__ . '/vendor/autoload.php';
    use Uspdev\Pix;

	 // Inicialização do serviço com as credenciais criadas
	 // [Ambiente de DEV]: ('usuario', 'senha', true)
	 // [Ambiente de PRD]: solicitar credenciais em https://servicos.sti.usp.br/ws-boleto/
    $pix = new Pix('usuario', 'senha', $ambiente_dev);

    /* array com campos mínimos para geração do pix */
    $data = array(
        'codigoUnidadeDespesa' => 8,
        'codigoFonteRecurso' => 32,
        'estruturaHierarquica' => '\FFLCH\SCINFOR',
        'valor' => 18.20,
        'tipoPessoa' => 'PF',
        'docPesOrg' => '00000000191',
        'nomePesOrg' => 'Fulano',
        'emailPesOrg' => 'fulano@usp.br',
        'infoCobranca' => 'Teste de geração de pix',
    );

	// [Método 'gerar'] gerar pix
    $gerar = $pix->gerar($data);
    if($gerar['status']) {
        $codigoIDPix = $gerar['value']['idfpix'];

		 // [Método 'situacao'] resgatar informações do pix
        print_r($pix->situacao($codigoIDPix));

		 // [Método 'obter'] recupera o QR Code do pix
        $obter = $pix->obter($codigoIDPix);
    }
