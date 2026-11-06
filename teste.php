<?php

require __DIR__ . '/vendor/autoload.php';

use Uspdev\Pix;

// coloque abaixo as credenciais da conta na STI para utilização do WSPIX, e um terceiro parâmetro opcional informando se deve executar em ambiente de desenvolvimento
$pix = new Pix('user', 'password');

$data = [
    'codigoUnidadeDespesa' => 1,
    'codigoFonteRecurso' => 423,
    'estruturaHierarquica' => '\DISTRIBUIDOR',
    'valor' => 15.50,
    'tipoPessoa' => 'PF',
    'docPesOrg' => '00000000191',
    'nomePesOrg' => 'Teste Pix',
    'emailPesOrg' => 'testepix@usp.br',
    'infoCobranca' => 'Teste de geração de Pix',
];

echo "Testando geração...\n";
$resultado = $pix->gerar($data);
print_r($resultado);

if ($resultado['status']) {
    $idfpix = $resultado['value']['idfpix'];
    
    echo "\nTestando situação para o ID: {$idfpix}...\n";
    print_r($pix->situacao($idfpix));

    echo "\nTestando obter (Copia e Cola)...\n";
    print_r($pix->obter($idfpix));
}
