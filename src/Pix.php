<?php

namespace Uspdev;

class Pix {
    
    public const WSPIX_SERVICO_GERAR_PIX = '/pix/gerar';
    public const WSPIX_SERVICO_CONSULTAR_PIX = '/pix/';
    
    private const WSPIX_URL_DEV = 'https://api-dev.portalservicos.usp.br/wspix/api';
    private const WSPIX_URL_PRD = 'https://uspdigital.usp.br/wspix/api';
    
    private $user;
    private $password;
    private $baseUrl;
    private $erro;

    public function __construct($user, $password, $dev = false) {
        $this->user = $user;
        $this->password = $password;
        $this->baseUrl = self::WSPIX_URL_PRD;
        
        if ($dev)
            $this->baseUrl = self::WSPIX_URL_DEV;
    }

    /**
     * Gera Pix integrado com o Sistema MercurioWeb
     * 
     * @param mixed $data   Array com os campos representando os parâmetros de entrada do serviço conforme especificação do WSPIX
     * @return array        Array associativo contendo o status de sucesso (na chave 'status') e os dados retornados pelo WSPIX (na chave 'value')
     */
    public function gerar($data) {
        $return = array('status' => false, 'value' => "");
        
        /* sanitização de charset ASCII e formatação decimal exigidas pelo WSPIX */
        if (isset($data['nomePesOrg']))
            $data['nomePesOrg'] = $this->sanitizeString($data['nomePesOrg'], 150);
        if (isset($data['infoCobranca']))
            $data['infoCobranca'] = $this->sanitizeString($data['infoCobranca'], 140);
        if (isset($data['docPesOrg']) && isset($data['tipoPessoa']) && in_array($data['tipoPessoa'], array('PF', 'PJ')))
            $data['docPesOrg'] = preg_replace('/\D/', '', (string) $data['docPesOrg']);
        if (isset($data['valor']) && is_numeric($data['valor']))
            $data['valor'] = number_format((float) $data['valor'], 2, ',', '');
        if (isset($data['expiracao']))
            $data['expiracao'] = (int) $data['expiracao'];
        else
            $data['expiracao'] = 86400;
        if (isset($data['codigoFonteRecurso']))
            $data['codigoFonteRecurso'] = (int) $data['codigoFonteRecurso'];
        if (isset($data['codigoUnidadeDespesa']))
            $data['codigoUnidadeDespesa'] = (int) $data['codigoUnidadeDespesa'];

        $request = $this->call('POST', self::WSPIX_SERVICO_GERAR_PIX, $data);
        
        if ($this->erro)
            $return['value'] = $this->erro;
        else {
            $return['status'] = true;
            if(!empty($request) && is_array($request)) {
                if (isset($request['idfpix']))
                    $request['idfpix'] = (string) $request['idfpix'];
                if (isset($request['qrCode']))
                    $request['qrCode'] = (string) $request['qrCode'];
                if (isset($request['qrCodeImgBase64']))
                    $request['qrCodeImgBase64'] = (string) $request['qrCodeImgBase64'];
                $return['value'] = $request;
            }
        }
        
        return $return;        
    }

    /**
     * Retorna a situação atual do pix (ativo, pago, etc.)
     *
     * @param string $codigoIDPix    Código de identificação do pix
     * @return array                 Array associativo contendo o status de sucesso (na chave 'status') e um array com os valores do pix (na chave 'value')
     */
    public function situacao($codigoIDPix) {
        $return = array('status' => false, 'value' => "");

        $query = array('verificar' => '1');
        $request = $this->call('GET', self::WSPIX_SERVICO_CONSULTAR_PIX . urlencode($codigoIDPix), array(), $query);
        
        if ($this->erro)
            $return['value'] = $this->erro;
        else {
            $return['status'] = true;
            if (!empty($request) && is_array($request)) {
                $return['value'] = array();
                $return['value']['situacao'] = isset($request['status']) ? (string) $request['status'] : '';
                $return['value']['valorCobrado'] = isset($request['valor']) ? (string) $request['valor'] : '';
                $return['value']['dataPagamento'] = isset($request['dataPag']) ? (string) $request['dataPag'] : '';
                $return['value']['qrCode'] = isset($request['qrCode']) ? (string) $request['qrCode'] : '';
                $return['value']['qrCodeImgBase64'] = isset($request['qrCodeImgBase64']) ? (string) $request['qrCodeImgBase64'] : '';
            }
        }
        
        return $return;
    }

    /**
     * Retorna o pix em string copia e cola/QR Code
     *
     * @param string $codigoIDPix    Código de identificação do pix
     * @return array                 Array associativo contendo o status de sucesso (na chave 'status') e o Pix em string copia e cola/QR Code (na chave 'value')
     */
    public function obter($codigoIDPix) {
        $return = array('status' => false, 'value' => "");
        
        $situacao = $this->situacao($codigoIDPix);
        
        if (!$situacao['status'])
            $return['value'] = $situacao['value'];
        else {
            $return['status'] = true;
            $return['value'] = $situacao['value'];
        }
        
        return $return;
    }

    private function sanitizeString($value, $maxLength) {
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $value);
        if (!is_string($normalized) || $normalized === '')
            $normalized = (string) $value;

        $normalized = preg_replace('/[^\x20-\x7E]/', '', $normalized) ?? '';
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? '';
        $normalized = trim($normalized);

        return substr($normalized, 0, $maxLength);
    }

    /**
     * Executa a chamada HTTP REST para o WSPIX usando cURL nativo
     */
    private function call($method, $path, $body = array(), $query = array()) {
        $this->erro = null;

        $url = rtrim($this->baseUrl, '/') . $path;
        if (!empty($query))
            $url .= '?' . http_build_query($query);

        $ch = curl_init();

        $headers = array(
            'X-Username: ' . $this->user,
            'X-Password: ' . $this->password,
            'Accept: application/json'
        );

        if ($method === 'POST') {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            $this->erro = 'Falha na comunicação com o WSPIX: ' . $curlError;
            return false;
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $this->erro = 'WSPIX retornou erro HTTP ' . $httpCode . ': ' . $this->obterDetalheErro($response);
            return false;
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            $this->erro = 'Resposta inválida do WSPIX: ' . $response;
            return false;
        }

        return $data;
    }

    private function obterDetalheErro($rawResponse) {
        $decoded = json_decode($rawResponse, true);
        if ((json_last_error() === JSON_ERROR_NONE) && is_array($decoded)) {
            if (isset($decoded['message']))
                return $decoded['message'];
            if (isset($decoded['error']))
                return is_string($decoded['error']) ? $decoded['error'] : json_encode($decoded['error']);
        }
        return (string) $rawResponse;
    }
}
