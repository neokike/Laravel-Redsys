<?php
namespace Neokike\Redsys;
use Exception;
/**
* Ceca
* @package sermepa
* @author Eduardo Díaz
* @forked Pedro Gorrin
* @since 1.0.0
*/
/**
 * Class Sermepa
 */
class Redsys{

  /******  Array de DatosEntrada ******/
    var $vars_pay = array();
    public $entorno = 'https://sis-t.redsys.es:25443/sis/realizarPago';
    private $nameForm = 'frm';

    /******  Set parameter ******/
    public function setParameter($key,$value){
        $this->vars_pay[$key]=$value;
    }

    /******  Get parameter ******/
    public function getParameter($key){
        return $this->vars_pay[$key];
    }
    
    
    //////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////
    ////////////                    FUNCIONES AUXILIARES:                             ////////////
    //////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////
    

    /******  3DES public function  ******/
    private function encrypt_3DES($message, $key){
        // Se establece un IV por defecto
        $bytes = array(0,0,0,0,0,0,0,0); //byte [] IV = {0, 0, 0, 0, 0, 0, 0, 0}
        $iv = implode(array_map("chr", $bytes)); //PHP 4 >= 4.0.2

        // Se cifra
        $ciphertext = mcrypt_encrypt(MCRYPT_3DES, $key, $message, MCRYPT_MODE_CBC, $iv); //PHP 4 >= 4.0.2
        return $ciphertext;
    }

    /******  Base64 public functions  ******/
    private function base64_url_encode($input){
        return strtr(base64_encode($input), '+/', '-_');
    }
    private function encodeBase64($data){
        $data = base64_encode($data);
        return $data;
    }
    private function base64_url_decode($input){
        return base64_decode(strtr($input, '-_', '+/'));
    }
    private function decodeBase64($data){
        $data = base64_decode($data);
        return $data;
    }

    /******  MAC public function ******/
    private function mac256($ent,$key){
        $res = hash_hmac('sha256', $ent, $key, true);//(PHP 5 >= 5.1.2)
        return $res;
    }

    
    //////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////
    ////////////       FUNCIONES PARA LA GENERACIÓN DEL FORMULARIO DE PAGO:           ////////////
    //////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////
    
    /******  Obtener Número de pedido ******/
    public function getOrder(){
        $numPedido = "";
        if(empty($this->vars_pay['DS_MERCHANT_ORDER'])){
            $numPedido = $this->vars_pay['Ds_Merchant_Order'];
        } else {
            $numPedido = $this->vars_pay['DS_MERCHANT_ORDER'];
        }
        return $numPedido;
    }
    /******  Convertir Array en Objeto JSON ******/
    public function arrayToJson(){
        $json = json_encode($this->vars_pay); //(PHP 5 >= 5.2.0)
        return $json;
    }
    public function createMerchantParameters(){
        // Se transforma el array de datos en un objeto Json
        $json = $this->arrayToJson();
        // Se codifican los datos Base64
        return $this->encodeBase64($json);
    }
    public function createMerchantSignature($key){
        // Se decodifica la clave Base64
        $key = $this->decodeBase64($key);
        // Se genera el parámetro Ds_MerchantParameters
        $ent = $this->createMerchantParameters();
        // Se diversifica la clave con el Número de Pedido
        $key = $this->encrypt_3DES($this->getOrder(), $key);
        // MAC256 del parámetro Ds_MerchantParameters
        $res = $this->mac256($ent, $key);
        // Se codifican los datos Base64
        return $this->encodeBase64($res);
    }
    


    //////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////
    //////////// FUNCIONES PARA LA RECEPCIÓN DE DATOS DE PAGO (Notif, URLOK y URLKO): ////////////
    //////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////

    /******  Obtener Número de pedido ******/
    public function getOrderNotif(){
        $numPedido = "";
        if(empty($this->vars_pay['Ds_Order'])){
            $numPedido = $this->vars_pay['DS_ORDER'];
        } else {
            $numPedido = $this->vars_pay['Ds_Order'];
        }
        return $numPedido;
    }
    public function getOrderNotifSOAP($datos){
        $posPedidoIni = strrpos($datos, "<Ds_Order>");
        $tamPedidoIni = strlen("<Ds_Order>");
        $posPedidoFin = strrpos($datos, "</Ds_Order>");
        return substr($datos,$posPedidoIni + $tamPedidoIni,$posPedidoFin - ($posPedidoIni + $tamPedidoIni));
    }
    public function getRequestNotifSOAP($datos){
        $posReqIni = strrpos($datos, "<Request");
        $posReqFin = strrpos($datos, "</Request>");
        $tamReqFin = strlen("</Request>");
        return substr($datos,$posReqIni,($posReqFin + $tamReqFin) - $posReqIni);
    }
    public function getResponseNotifSOAP($datos){
        $posReqIni = strrpos($datos, "<Response");
        $posReqFin = strrpos($datos, "</Response>");
        $tamReqFin = strlen("</Response>");
        return substr($datos,$posReqIni,($posReqFin + $tamReqFin) - $posReqIni);
    }
    /******  Convertir String en Array ******/
    public function stringToArray($datosDecod){
        $this->vars_pay = json_decode($datosDecod, true); //(PHP 5 >= 5.2.0)
    }
    public function decodeMerchantParameters($datos){
        // Se decodifican los datos Base64
        $decodec = $this->base64_url_decode($datos);
        return $decodec;    
    }
    public function createMerchantSignatureNotif($key, $datos){
        // Se decodifica la clave Base64
        $key = $this->decodeBase64($key);
        // Se decodifican los datos Base64
        $decodec = $this->base64_url_decode($datos);
        // Los datos decodificados se pasan al array de datos
        $this->stringToArray($decodec);
        // Se diversifica la clave con el Número de Pedido
        $key = $this->encrypt_3DES($this->getOrderNotif(), $key);
        // MAC256 del parámetro Ds_Parameters que envía Redsys
        $res = $this->mac256($datos, $key);
        // Se codifican los datos Base64
        return $this->base64_url_encode($res);  
    }
    /******  Notificaciones SOAP ENTRADA ******/
    public function createMerchantSignatureNotifSOAPRequest($key, $datos){
        // Se decodifica la clave Base64
        $key = $this->decodeBase64($key);
        // Se obtienen los datos del Request
        $datos = $this->getRequestNotifSOAP($datos);
        // Se diversifica la clave con el Número de Pedido
        $key = $this->encrypt_3DES($this->getOrderNotifSOAP($datos), $key);
        // MAC256 del parámetro Ds_Parameters que envía Redsys
        $res = $this->mac256($datos, $key);
        // Se codifican los datos Base64
        return $this->encodeBase64($res);   
    }
    /******  Notificaciones SOAP SALIDA ******/
    public function createMerchantSignatureNotifSOAPResponse($key, $datos, $numPedido){
        // Se decodifica la clave Base64
        $key = $this->decodeBase64($key);
        // Se obtienen los datos del Request
        $datos = $this->getResponseNotifSOAP($datos);
        // Se diversifica la clave con el Número de Pedido
        $key = $this->encrypt_3DES($numPedido, $key);
        // MAC256 del parámetro Ds_Parameters que envía Redsys
        $res = $this->mac256($datos, $key);
        // Se codifican los datos Base64
        return $this->encodeBase64($res);   
    }

   public function create_angular_form($nombre = 'submitsermepa',$texto='Enviar',$url = ' https://sis.redsys.es/sis/realizarPago')
    {

        $formulario='
        <form action="'.$url.'" method="post" id="'.$this->nameForm.'" name="'.$this->nameForm.'" >
            <input type="hidden" name="Ds_SignatureVersion" ng-value="vm.tdc.version" />
            <input type="hidden" name="Ds_MerchantParameters" ng-value="vm.tdc.params" />
            <input type="hidden" name="Ds_Signature" ng-value="vm.tdc.signature" />
        ';

        $formulario.=$this->hiddenSubmit($nombre,$texto);
        $formulario.='
        </form>        
        ';

    
        return $formulario;
    }

    public function hiddenSubmit($nombre = 'submitsermepa',$texto='Enviar')
    {
        if(strlen(trim($nombre))==0)
            throw new Exception('Asigne nombre al boton submit');

        $btnsubmit = '<input type="submit" style="display: none" name="'.$nombre.'" id="'.$nombre.'" value="'.$texto.'" />';
        return $btnsubmit;
    }

    public function submit($nombre = 'submitsermepa',$texto='Enviar')
    {
        if(strlen(trim($nombre))==0)
            throw new Exception('Asigne nombre al boton submit');

        $btnsubmit = '<input type="submit" name="'.$nombre.'" id="'.$nombre.'" value="'.$texto.'" />';
        return $btnsubmit;
    }

    public function set_entorno($entorno='pruebas')
    {
        if(trim($entorno) == 'real'){
            //real
            $this->entorno='https://sis.redsys.es/sis/realizarPago';
        }
        else{
            //pruebas
            $this->entorno ='https://sis-t.redsys.es:25443/sis/realizarPago';
        }
    }


    public function comprobar($postData='',$kc)
    {
        if (!empty( $postData ) ) {//URL DE RESP. ONLINE
            
            $version = $postData["Ds_SignatureVersion"];
            $datos = $postData["Ds_MerchantParameters"];
            $signatureRecibida = $postData["Ds_Signature"];
            

            $decodec = $this->decodeMerchantParameters($datos);    
            $firma = $this->createMerchantSignatureNotif($kc,$datos);  

            if ($firma === $signatureRecibida){
                return true;
            } else {
                return false;
            }
        }else{
            return false;
        }
    }
}