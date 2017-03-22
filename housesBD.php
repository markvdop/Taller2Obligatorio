<?php

require_once('datos.php');
try {
    if (isset($_POST['pregunta']) && !empty($_POST['pregunta'])) {
        $pregunta = $_POST['pregunta'];
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $id = $_POST['id'];
            guardarPregunta($id, $pregunta);
            $ret = array('OK' => 'La pregunta se registro correctamente');
            echo json_encode($ret);
        }
    }
    if (isset($_POST['editar']) && !empty($_POST['editar']) && $_POST['editar']=='true') {
        actualizarDatos($_POST);
    }
}catch (Exception $ex) {
    echo $ex->getMessage();
}

function getCasas($pagina, $resultadosPorPagina = 10) {

    $hasta = ($pagina) * $resultadosPorPagina;
    $desde = $hasta - ($resultadosPorPagina - 1);

    $cn = conectar();

    $cn->consulta("
            SELECT p.*, b.nombre FROM propiedades p
            INNER JOIN barrios b ON p.barrio_id = b.id
            WHERE p.eliminado = 0
            LIMIT :from, :to
        ", array(
        array('from', $desde, 'int'),
        array('to', $resultadosPorPagina, 'int')
    ));
    $casas = array();
    $a = $cn->cantidadRegistros();
    for ($i = 0; $i < $a; $i++) {
        $casa = $cn->siguienteRegistro();
        array_push($casas, $casa);
    }

    $cn->consulta("
            SELECT count(*) as total
            FROM propiedades
        ");

    $total = $cn->siguienteRegistro()['total'] / $resultadosPorPagina;

    $cn->desconectar();

    return array(
        'casas' => $casas,
        'total' => ceil($total)
    );
}

function getCasa($id = 0) {
    $cn = conectar();

    $cn->consulta("
            SELECT p.id, p.tipo, p.operacion, p.precio, p.mts2, p.habitaciones, p.banios, p.garage, p.titulo, p.texto, p.barrio_id, b.nombre as `barrio`, c.nombre as `ciudad`
            FROM `propiedades` p, `barrios` b, `ciudades` c
            WHERE p.id = :id
            AND p.barrio_id = b.id
            AND b.ciudad_id = c.id
        ", array(
        array('id', $id, 'int')
    ));
    $casa = $cn->siguienteRegistro();

    return array(
        'casa' => $casa
    );
}

function getPreguntas($id = 0) {
    $cn = conectar();

    $cn->consulta("
            SELECT *
            FROM `preguntas` p
            WHERE p.id_propiedad = :id
            AND p.fecha_respuesta IS NOT NULL
        ", array(
        array('id', $id, 'int')
    ));
    $preguntas = array();
    $a = $cn->cantidadRegistros();
    for ($i = 0; $i < $a; $i++) {
        $pregunta = $cn->siguienteRegistro();
        array_push($preguntas, $pregunta);
    }
    return array(
        'preguntas' => $preguntas
    );
}

function guardarPregunta($id = 0, $pregunta) {
    $cn = conectar();
    $date = date('Y-m-d H:i:s');
    $cn->consulta("
           INSERT INTO `preguntas`
           (`id`, `id_propiedad`, `fecha`, `texto`, `respuesta`, `fecha_respuesta`)
           VALUES (NULL, :id, :date, :pregunta, NULL, NULL);
       ", array(
        array('id', $id, 'int'),
        array('pregunta', $pregunta, 'string'),
        array('date', $date, 'string')
    ));
}

function actualizarDatos($datos) {
    $id = intval($datos[idCasa]);
    $cn = conectar();
    $cn->consulta("
        UPDATE propiedades SET 
        `tipo` = :tipo,
        `operacion`= :operacion,
        `barrio_id` = :barrio_id,
        `precio` = :precio,
        `mts2`= :mts2,
        `habitaciones` = :habitaciones,
        `banios` = :banios,
        `garage` = :garage,
        `texto` = :texto
           WHERE  id = :id;
       ", array(
        array('tipo', $datos[propiedad], 'string'),
        array('operacion', $datos[operacion], 'string'),
        array('barrio_id', $datos[barrio], 'string'),
        array('precio', $datos[precio], 'string'),
        array('operacion', $datos[operacion], 'string'),
        array('mts2', $datos[mts2], 'string'),
        array('habitaciones', $datos[habitaciones], 'string'),
        array('banios', $datos[banios], 'string'),
        array('garage', $datos[garage], 'garage'),
        array('texto', $datos[texto], 'texto'),
        array('id', $id, 'string')
    ));
    header("location: ./housePage.php");
}

function buscarCasas($pagina, $operacion, $ciudad, $avanzada, $propiedad
        , $barrio, $habitaciones, $pDesde, $pHasta, $garaje, $orden, $forma) {
    $resultadosPorPagina = 10;
    $hasta = ($pagina) * $resultadosPorPagina - 1;
    $desde = $hasta - ($resultadosPorPagina) + 1;
    $cn = conectar();
    
    $casasSql = "SELECT p.*, b.nombre ";
    $countSql = "SELECT count(*) as total ";

    $sql = "FROM propiedades p, barrios b
            WHERE p.operacion = :op AND b.id = p.barrio_id AND b.ciudad_id = :idCiudad AND p.eliminado = 0";

    $params = array(
        array('op', $operacion, 'string'),
        array('idCiudad', $ciudad, 'int')
    );

    if ($avanzada == TRUE) {
        if ($propiedad != NULL) {
            $sql = $sql . " AND p.tipo = :tipoProp";
            array_push($params, array('tipoProp', $propiedad, 'string'));
        }
        if ($barrio != NULL) {
            $sql = $sql . " AND p.barrio_id = :idBarrio";
            array_push($params, array('idBarrio', $barrio, 'int'));
        }
        if ($habitaciones != NULL) {
            $sql = $sql . " AND p.habitaciones = :hab";
            array_push($params, array('hab', $habitaciones, 'int'));
        }
        if ($pDesde != NULL) {
            $sql = $sql . " AND p.precio >= :pDesde";
            array_push($params, array('pDesde', $pDesde, 'int'));
        }
        if ($pHasta != NULL) {
            $sql = $sql . " AND p.precio <= :pHasta";
            array_push($params, array('pHasta', $pHasta, 'int'));
        }
        if ($garaje != NULL) {
            $sql = $sql . " AND p.garaje = :garaje";
            array_push($params, array('garaje', $garaje, 'int'));
        }
    }

    if ($orden != NULL) {
        $sql = $sql . " ORDER BY " . $orden. " " . ($forma === NULL ? "ASC" : $forma);
    }

    $casasSql = $casasSql . $sql . " LIMIT :from, :to";
    $paramsCasas = $params;
    array_push($paramsCasas, array('from', $desde, 'int'),
        array('to', $resultadosPorPagina, 'int'));
    $cn->consulta($casasSql, $paramsCasas);
    $casas = array();
    $a = $cn->cantidadRegistros();
    for ($i = 0; $i < $a; $i++) {
        $casa = $cn->siguienteRegistro();
        array_push($casas, $casa);
    }
    $countSql = $countSql . $sql;
    $cn->consulta($countSql, $params);
    $total = $cn->siguienteRegistro()['total'] / $resultadosPorPagina;
    $cn->desconectar();

    return array(
        'casas' => $casas,
        'total' => ceil($total)
    );
}

function getPromedioPrecioBarrio($idBarrio, $tipoOp){
    $cn = conectar();
    $cn->consulta("
            SELECT p.precio, p.mts2
            FROM propiedades p
            WHERE p.eliminado = 0 AND p.barrio_id = :idBarrio 
            AND p.operacion = :tipoOp
        ", array(
        array('idBarrio', $idBarrio, 'int'),
        array('tipoOp', $tipoOp, 'string')
    ));
    $tuplas = array();
    $a = $cn->cantidadRegistros();
    for ($i = 0; $i < $a; $i++) {
        $tupla = $cn->siguienteRegistro();
        array_push($tuplas, $tupla);
    }

    $cn->desconectar();
        
    $promedioAcumulado = 0;
    foreach ($tuplas as $par){
        $promedioAcumulado += $par[precio] / $par[mts2];
    }
    $prom = $promedioAcumulado / $a;
    
    return number_format($prom, 2, '.', '');
}
