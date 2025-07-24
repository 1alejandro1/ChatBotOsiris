<?php
define('GEMINI_API_KEY','AIzaSyDNnfx7cQwXuBrJhWiN-K5GM_Bbh1t-1v0');
define('MODEL','gemini-2.0-flash');
define('BASEURL','https://generativelanguage.googleapis.com/v1beta');

header('Content-Type: application/json');

function normalizar($s) {
    $map = [
      'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N',
      'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n',
      'Ü'=>'U','ü'=>'u'
    ];
    return strtr($s, $map);
}

function respuestasGenerales($mensaje) {
    $m = normalizar(trim(strtolower($mensaje)));
    if (preg_match('/\bhola\b/u',     $m)) {
        $sal = [
          "¡Hola! ¿Cómo puedo ayudarte hoy con nuestros equipos electrónicos?",
          "¡Buen día! Estoy aquí para asistirte con cualquier consulta.",
          "¡Saludos! ¿En qué te puedo servir hoy?"
        ];
        return $sal[array_rand($sal)];
    }
    if (preg_match('/\bcomo\s*estas\b/u', $m)) {
        $est = [
          "Estoy muy bien, gracias por preguntar. ¿Qué necesitas saber?",
          "¡Genial! Listo para ayudarte con tus compras.",
          "Todo en orden por aquí. ¿Tienes alguna duda sobre nuestros productos?"
        ];
        return $est[array_rand($est)];
    }
    if (preg_match('/\bque\s*puedes\s*hacer\b/u', $m)) {
        $cap = [
          "Puedo recomendarte productos, informar sobre precios y resolver dudas técnicas.",
          "Te ayudo a encontrar la laptop o smartphone ideal y a despejar cualquier pregunta.",
          "Asesoro en características, garantías y disponibilidad de nuestros equipos."
        ];
        return $cap[array_rand($cap)];
    }
    return null;
}

function cargarProductos() {
    $productos = [];
    if (($f = fopen("productos.csv","r")) !== false) {
        $cab = fgetcsv($f);
        while (($row = fgetcsv($f)) !== false) {
            $productos[] = array_combine($cab, $row);
        }
        fclose($f);
    }
    return $productos;
}

function buscarProducto($mensaje) {
    foreach (cargarProductos() as $p) {
        if (stripos($mensaje, $p["nombre"]) !== false) {
            return [
              "type" => "product",
              "data" => [
                "nombre"      => $p["nombre"],
                "categoria"   => $p["categoria"],
                "precio"      => $p["precio"],
                "descripcion" => $p["descripcion"],
                "url_imagen"  => $p["url_imagen"],
                "url_producto"=> $p["url_producto"]
              ]
            ];
        }
    }
    return null;
}

function consultarGemini($mensaje) {
    $payload = [
        "contents" => [
            [
              "role"=>"system",
              "parts"=>[["text"=>"Eres un asistente de ventas de equipos electrónicos con tono amigable y profesional."]]
            ],
            [
              "role"=>"user",
              "parts"=>[["text"=>$mensaje]]
            ]
        ]
    ];
    $ch = curl_init(BASEURL."/models/".MODEL.":generateContent?key=".GEMINI_API_KEY);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload)
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    $j = json_decode($resp, true);
    $text = $j['candidates'][0]['content']['parts'][0]['text']
          ?? "Lo siento, no pude procesar tu solicitud.";
    return ["type"=>"text", "data"=>$text];
}

$raw   = file_get_contents("php://input");
$input = json_decode($raw, true)["message"] ?? "";

if (($gen = respuestasGenerales($input)) !== null) {
    $output = ["type"=>"text",    "data"=>$gen];
}
elseif (($prod = buscarProducto($input)) !== null) {
    $output = $prod; 
}
else {
    $output = consultarGemini($input);
}
echo json_encode($output);