# Xupopter 

[![Build Status](https://travis-ci.org/tetreum/xupopter.svg?branch=master)](https://travis-ci.org/tetreum/xupopter)
[![License](https://poser.pugx.org/tetreum/xupopter/license.svg)](https://packagist.org/packages/tetreum/xupopter)

Xupopter crawlea las páginas más conocidas de compraventa y alquiler de pisos de España.

Listado de Providers que crawlea:
- Pisos.com
- Habitaclia.com
- Fotocasa.es
- Idealista.com

#### ¿Por qué?
La usabilidad de algunas de dichas páginas deja mucho que desear.
Otras pecan de falta de filtros (Estoy harto de descartar casas en venta con regalo [a.k.a usufructo] incluído).

#### Instalación

    composer require tetreum/xupopter dev-master


#### ¡Un provider ha dejado de funcionar! ¡No crawlea X dato!
Perfecto, arréglalo y haz PR.

#### ¡Algunos resultados no son crawleados!
Xupopter exige un mínimo de características (ver más abajo) a crawlear de la casa, como son imágenes y m2.
Los resultados que no cumplan con este mínimo, serán descartados.

House object:
- title
- description
- price
- url
- meters
- images

-- Optional parameters:
- hasAirConditioner
- hasElevator
- floor

#### Ejemplo de uso:

Un cron diario.

- bootstrap.php:
```php
<?php
// Includes vendor libraries
require "vendor/autoload.php";

use Xupopter\System\App as Xupopter;

define('APP_ROOT', __DIR__ . DIRECTORY_SEPARATOR);
date_default_timezone_set("Europe/Madrid");

// Include configurations and global constants
Xupopter::$config = require "conf.php";
```

- conf.php
```php
<?php
return [
	'tmp' => APP_ROOT . 'tmp/', // in debug mode, html curls will be cached
	'mode' => 'production',
	'debug' => false,
	'test' => false,
	'providers' => [
		'Habitaclia' => [
            "/comprar-vivienda-en-barcelones/provincia_barcelona/listainmuebles.htm?bolIsFiltro=0&tip_op_origen=V&hUserClickFilterButton=&filtro_periodo=0&hMinLat=&hMinLon=&hMaxLat=&hMaxLon=&hUseLatLonFilters=&hNumPointsMapa=&ordenar=pvp_inm_desc&f_con_fotos=0"
    	],
		'Fotocasa' => [
			'/comprar/casas/barcelona-capital/listado?crp=1&ts=barcelona%20capital&llm=724,9,8,232,376,8019,0,0,0&f=publicationdate&o=asc&opi=36&ftg=true&pgg=false&odg=false&fav=false&grad=false&fss=false&mode=3&cu=es-es&pbti=2&nhtti=1&craap=1&fs=true&lon=0&lat=0&fav=false'
		],
		'Pisos' => [
			'/venta/pisos-barcelones/desc/'
		],
		"Idealista" => [
			"/venta-viviendas/barcelona-barcelona/?ordenado-por=precio-desc"
		]
	],
    "avoid" => [
        'text' => [ // text == title + description
            "usufructo",
            "beneficiario",
        ],
        "location" => [
            "ciutat meridiana",
            "trinitat nova",
            "trinitat vella",
            "carmel",
            "roquetes"
        ]
    ]
];
```

- cron.php
```php
<?php
set_time_limit(0);

require 'bootstrap.php';

use Xupopter\System\App;

foreach (App::config('providers') as $pName => $paths)
{
	App::runProvider($pName, $paths, function ($house) {
	    // callback fired for each crawled house
		// echo $house->title . " - " . $house->price . "€";
		// $mysql->query("INSERT INTO houses...
	});
}
```


#### ToDo

- Función para detectar de qué proveedor es una url i mandárselo al mismo.
- Refactorizar la implementación del callback
- Crawlear la fecha de publicación de Idealista
- Añadir más proveedores
