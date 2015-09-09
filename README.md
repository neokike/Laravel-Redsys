Historia
--------
Esta clase nace porque no te encontraba una clase de pasarela de pagos (TPV) que se pueda integrar directamente en una web, existen
muchas pero para varios CMS y no me servian, solo quería montar algo fácil que pueda usar, los ejemplos que vienen en la documentación oficial era muy simple así que decidi realizar esta clase y ahora lo comparto con todos.
    
    Valido para Sermepa y Redsys.

Introducción
------------
La clase sermepa sirve para generar el formulario que se comunicará con la pasarela de pagos que usan muchos bancos como: Sabadell, Lacaixa, etc.

Es una versión que ira creciendo y actualizandose poco a poco y mejorandolo.
Si lo usas en algún proyecto y te ayudo en algo no dudas en decirmelo

Requerimientos mínimos
----------------------
PHP 5.2.0

Creditos
--------
    Clase creada por Eduardo Diaz, Madrid 2012
    Twitter: @eduardo_dx


Instalación
-----------
**Si usas composer tienes 2 opciones**
    
1.- Por linea de comandos
```bash
    composer require sermepa/sermepa
```    
2.- Creas o agregas a tu archivo **composer.json** la siguiente dependencia:

```json
    {
       "require": {
          "sermepa/sermepa": "1.0.*"
       }
    }
```
    
Luego ejecutas:
    
    composer update


**Si en caso contrario no usas composer, bastará con clonar el repositorio**
```
git clone git@github.com:ssheduardo/sermepa.git
```

Como usar la clase
------------------

**Métodos útiles**

    //Asignar nombre a id y name del formulario
    $pasarela->set_nameform('nombre_formulario');   

    //Generar el input submit (si en caso no se usa javascript u otro)
    $pasarela->submit('nombre_submit','texto_del_boton');

    //Generar formulario
    $pasarela->create_form();

**Ejemplos**
Primero asignamos los parámetros
```php
    try{
        $pasarela = new Sermepa();
        $pasarela->importe(10.50);
        $pasarela->pedido(date('ymdHis'));  //generamos el número de recibo usando date por ejemplo
        $pasarela->clave('xxxxxxx');    //clave asignada por el banco.
        $pasarela->codigofuc('xxxxxxx');
        $pasarela->producto_descripcion('Demo');
        $pasarela->titular('Usuario');
        $pasarela->nombre_comercio('Ejemplo');
        //Si el comercio tiene notificación "on-line". URL del comercio que recibirá un post con los datos de la transacción .
        $pasarela->url_notificacion('http://www.example.com/notificacion.php'); 
        $pasarela->url_ok('http://www.example.com/ok.php'); // Si le das aceptar finalizada la compra desde la pasarela de pagos
        $pasarela->url_ko('http://www.example.com/ko.php'); // Si le das cancelar desde la pasarela de pagos
        $pasarela->firma();

        //Generamos botón submit
        $pasarela->submit('nombre_submit','Pagar');
    }
    catch(Exception $e){
        echo $e->getMessage();   
    }
    $formulario = $pasarela->create_form();
    echo $formulario;
```
Con esto generamos el form para la comunicación con la pasarela de pagos.


Redirección automática

```php
    //Gracias por a la colaboración de jaumecornado (github)
    //Podemos forzar la redirección sin pasar por el método create_form()

    $pasarela->ejecutarRedireccion(); 
```    

[Esto método llamaría a **create_form** y lanzaría el submit por javacript]



Comprobación de Pago
--------------------

**Gracias por a la colaboración de markitosgv (github)**

Podemos comprobar si se ha realizado el pago correctamente. Para ello necesitamos setear la clave del banco y pasar la variable $_POST que nos devuelve en la URL de notificación o de retorno. Por ejemplo, en el fichero que es llamado por la URL de retorno:

```php
    try{
        $pasarela = new Sermepa();
        $pasarela->clave('xxxxxxx');    //clave asignada por el banco.
        if ($pasarela->comprobar($_POST)) {
            //acciones a realizar si es correcto, por ejemplo validar una reserva, mandar un mail de OK, guardar en bbdd o contactar con mensajería para preparar un pedido
        } else {
            //acciones a realizar si ha sido erroneo
        }
    }
    catch(Exception $e){
        echo $e->getMessage();
    }
```

>Nota:
    Por defecto se conecta por la pasarela de pruebas para cambiar a un entorno real usar el método: **set_entorno('real')**, con esto ya estará activo.


    
