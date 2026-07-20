<?php
/**
 * Catálogos del SAT para CFDI 4.0
 * Fuente: https://www.sat.gob.mx/aplicacion/operacion/31762/utiliza-el-catalogo-completo-de-claves-de-productos-y-servicios
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Sat_catalogs {
    
    // Formas de Pago (c_FormaPago)
    public static $formas_pago = [
        '01' => 'Efectivo',
        '02' => 'Cheque nominativo',
        '03' => 'Transferencia electrónica de fondos',
        '04' => 'Tarjeta de crédito',
        '05' => 'Monedero electrónico',
        '06' => 'Dinero electrónico',
        '08' => 'Vales de despensa',
        '12' => 'Dación en pago',
        '13' => 'Pago por subrogación',
        '14' => 'Pago por terceros',
        '15' => 'Condonação',
        '17' => 'Compensación',
        '23' => 'Novación',
        '24' => 'Confusión',
        '25' => 'Remisión de deuda',
        '26' => 'Prescripción o caducidad',
        '27' => 'A satisfacción',
        '28' => 'Tarjeta de débito',
        '29' => 'Tarjeta prepago',
        '30' => 'Por definir',
        '31' => 'Intermediario pagos',
        '32' => 'Emisión propia',
        '33' => 'Bono de descuento',
        '34' => 'Factoring',
        '35' => 'Reembolso',
        '36' => 'Subsidio',
        '37' => 'Donación',
        '38' => 'Premio',
        '39' => 'Rendimiento',
        '40' => 'Retorno',
        '41' => 'Reintegro',
        '42' => 'Saldo a favor',
        '43' => 'Saldo en contra',
        '44' => 'Crédito fiscal',
        '45' => 'Devolución',
        '46' => 'Indemnización',
        '47' => 'Liquidación',
        '48' => 'Pago diferido',
        '49' => 'Pago parcial',
        '50' => 'Pago total',
        '51' => 'Pago único',
        '52' => 'Pago periódico',
        '53' => 'Pago anticipado',
        '54' => 'Pago posterior',
        '55' => 'Pago simultáneo',
        '56' => 'Pago escalonado',
        '57' => 'Pago fraccionado',
        '58' => 'Pago diferido parcial',
        '59' => 'Pago diferido total',
        '60' => 'Pago parcial diferido',
        '61' => 'Pago total diferido',
        '99' => 'Otros'
    ];

    // Métodos de Pago (c_MetodoPago)
    public static $metodos_pago = [
        'PUE' => 'Pago en una sola exhibición',
        'PPD' => 'Pago en parcialidades o diferido'
    ];

    // Tipos de Comprobante (c_TipoComprobante)
    public static $tipos_comprobante = [
        'I' => 'Ingreso',
        'E' => 'Egreso',
        'T' => 'Traslado',
        'N' => 'Nómina',
        'P' => 'Pago'
    ];

    // Usos de CFDI (c_UsoCFDI) - Personas Morales
    public static $usos_cfdi_morales = [
        'G01' => 'Adquisición de mercancías',
        'G02' => 'Devoluciones, descuentos o bonificaciones',
        'G03' => 'Gastos en general',
        'I01' => 'Construcciones',
        'I02' => 'Mobiliario y equipo de oficina por inversiones',
        'I03' => 'Equipo de transporte',
        'I04' => 'Equipo de computo y accesorios',
        'I05' => 'Dados, troqueles, moldes, matrices y herramientas',
        'I06' => 'Comunicaciones telefónicas',
        'I07' => 'Satélites y otros equipos de comunicación',
        'I08' => 'Otra maquinaria y equipo',
        'D01' => 'Honorarios médicos, dentales y hospitalarios',
        'D02' => 'Gastos médicos por incapacidad o discapacidad',
        'D03' => 'Gastos funerarios',
        'D04' => 'Donativos',
        'D05' => 'Intereses reales efectivamente pagados por créditos hipotecarios',
        'D06' => 'Aportaciones voluntarias al SAR',
        'D07' => 'Primas por seguros de gastos médicos',
        'D08' => 'Gastos de transportación obligatoria',
        'D09' => 'Dependientes económicos',
        'D10' => 'Intereses por créditos hipotecarios',
        'S01' => 'Sin efectos fiscales',
        'CN01' => 'Compra de combustibles dentro de territorio nacional',
        'CP01' => 'Compra de combustibles fuera de territorio nacional',
        'CT01' => 'Combustibles para transporte terrestre',
        'CT02' => 'Combustibles para transporte marítimo',
        'CT03' => 'Combustibles para transporte aéreo',
        'CT04' => 'Combustibles para transporte férreo',
        'CA01' => 'Compra de alimentos en establecimientos ubicados en terminales terrestres, aeroportuarias y marítimas',
        'CO01' => 'Compra de bienes o servicios no identificados'
    ];

    // Regímenes Fiscales (c_RegimenFiscal)
    public static $regimenes_fiscales = [
        '601' => 'General de Ley Personas Morales',
        '603' => 'Personas Morales con Fines no Lucrativos',
        '604' => 'Ingresos por Honorarios',
        '605' => 'Sueldos y Salarios e Ingresos Asimilados a Salarios',
        '606' => 'Arrendamiento',
        '607' => 'Régimen de Enajenación o Adquisición de Bienes',
        '608' => 'Demás ingresos',
        '610' => 'Residentes en el Extranjero sin Establecimiento Permanente en México',
        '611' => 'Ingresos por Dividendos (socios y accionistas)',
        '612' => 'Personas Físicas con Actividades Empresariales y Profesionales',
        '614' => 'Ingresos por intereses',
        '615' => 'Régimen de los Ingresos por Obtención de Premios',
        '616' => 'Sin obligaciones fiscales',
        '620' => 'Sociedades Cooperativas de Producción que optan por diferir sus ingresos',
        '621' => 'Incorporación Fiscal',
        '622' => 'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras',
        '623' => 'Opcional para Grupos de Sociedades',
        '624' => 'Coordinados',
        '625' => 'Régimen de las Actividades Empresariales con fines lucrativos',
        '626' => 'Régimen de Actividad Empresarial',
        '628' => 'Hidrocarburos',
        '629' => 'Petroleros',
        '630' => 'Minería',
        '631' => 'Sector Financiero',
        '632' => 'Servicios de Plataforma Tecnológica',
        '633' => 'Régimen Simplificado de Confianza',
        '634' => 'Régimen Simplificado de Confianza para Actividades Empresariales',
        '635' => 'Régimen Simplificado de Confianza para Arrendamiento',
        '636' => 'Régimen Simplificado de Confianza para Servicios Profesionales'
    ];

    // Clave de Producto/Servicio (ejemplos comunes)
    // Nota: El catálogo completo tiene miles de entradas. Se incluyen las más usadas.
    public static $claves_prod_serv = [
        '01010101' => 'Carne de res',
        '01010102' => 'Carne de cerdo',
        '01010103' => 'Carne de ave',
        '01010104' => 'Carne de pescado',
        '01010105' => 'Mariscos y moluscos',
        '01010201' => 'Leche',
        '01010202' => 'Queso',
        '01010203' => 'Huevo',
        '01010301' => 'Pan',
        '01010302' => 'Tortilla',
        '01010303' => 'Pastas alimenticias',
        '01010401' => 'Frutas frescas',
        '01010402' => 'Verduras frescas',
        '01010501' => 'Azúcar',
        '01010502' => 'Sal',
        '01010503' => 'Café',
        '01010504' => 'Chocolate',
        '01010601' => 'Refrescos',
        '01010602' => 'Agua embotellada',
        '01010603' => 'Jugos',
        '01010701' => 'Cerveza',
        '01010702' => 'Vino',
        '01010703' => 'Licor',
        '40111501' => 'Consultoría en administración',
        '40111502' => 'Consultoría en finanzas',
        '40111503' => 'Consultoría en recursos humanos',
        '40111504' => 'Consultoría en marketing',
        '40111505' => 'Consultoría en tecnología',
        '40111601' => 'Servicios de contabilidad',
        '40111602' => 'Servicios de auditoría',
        '40111603' => 'Servicios legales',
        '40111701' => 'Servicios de publicidad',
        '40111702' => 'Servicios de diseño gráfico',
        '40111801' => 'Servicios de desarrollo de software',
        '40111802' => 'Servicios de hosting',
        '40111803' => 'Servicios de soporte técnico',
        '50111501' => 'Renta de equipo de cómputo',
        '50111502' => 'Renta de vehículos',
        '50111503' => 'Renta de maquinaria',
        '50111601' => 'Servicios de mantenimiento',
        '50111602' => 'Servicios de limpieza',
        '50111603' => 'Servicios de seguridad',
        '72151501' => 'Gasolina Magna',
        '72151502' => 'Gasolina Premium',
        '72151503' => 'Diesel',
        '99999999' => 'Servicio genérico (no identificado)'
    ];

    // Clave de Unidad (c_ClaveUnidad)
    public static $claves_unidad = [
        'ACT' => 'Actividad',
        'BAR' => 'Barra',
        'BOL' => 'Bolsa',
        'BOX' => 'Caja',
        'CAN' => 'Lata',
        'CON' => 'Contenido',
        'DOZ' => 'Docena',
        'H87' => 'Pieza',
        'KGM' => 'Kilogramo',
        'LTR' => 'Litro',
        'MTR' => 'Metro',
        'NIU' => 'Unidad Internacional',
        'PAQ' => 'Paquete',
        'SET' => 'Juego',
        'TON' => 'Tonelada',
        'XBX' => 'Caja',
        'XPK' => 'Paquete'
    ];

    // Objeto de Impuesto
    public static $objeto_imp = [
        '01' => 'No objeto de impuesto',
        '02' => 'Sí objeto de impuesto',
        '03' => 'Sí objeto de impuesto y obligado al desglose',
        '04' => 'Sí objeto de impuesto y no obligado al desglose'
    ];

    // Impuestos (c_Impuesto)
    public static $impuestos = [
        '001' => 'ISR',
        '002' => 'IVA',
        '003' => 'IEPS'
    ];

    // Tipo Factor
    public static $tipo_factor = [
        'Exento' => 'Exento',
        'Tasa' => 'Tasa',
        'Cuota' => 'Cuota'
    ];

    // Exportación
    public static $exportacion = [
        '01' => 'No aplica',
        '02' => 'Sí exporta'
    ];

    /**
     * Obtener lista de catálogos para selectores
     */
    public static function get_all_catalogs() {
        return [
            'formas_pago' => self::$formas_pago,
            'metodos_pago' => self::$metodos_pago,
            'tipos_comprobante' => self::$tipos_comprobante,
            'usos_cfdi_morales' => self::$usos_cfdi_morales,
            'regimenes_fiscales' => self::$regimenes_fiscales,
            'claves_prod_serv' => self::$claves_prod_serv,
            'claves_unidad' => self::$claves_unidad,
            'objeto_imp' => self::$objeto_imp,
            'impuestos' => self::$impuestos,
            'tipo_factor' => self::$tipo_factor,
            'exportacion' => self::$exportacion
        ];
    }

    /**
     * Validar si un valor existe en un catálogo
     */
    public static function validate($catalogo, $valor) {
        $data = self::get_all_catalogs();
        if (!isset($data[$catalogo])) {
            return false;
        }
        return array_key_exists($valor, $data[$catalogo]);
    }
}
