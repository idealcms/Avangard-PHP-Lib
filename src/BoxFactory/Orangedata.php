<?php
/**
 * Copyright Bykovskiy Maxim. Avangard (c) 2019.
 */

namespace Avangard\BoxFactory;

use GuzzleHttp\Client;
use OrangeDataClient\OrangeDataClient;

/**
 * Class orangedata
 *
 * @package Avangard\boxFactory
 */
class Orangedata implements BoxInterface
{
    /**
     * Object of orangedata library
     *
     * @var orangeData
     */
    protected $client;

    /**
     * Company's taxation system
     *
     * @var mixed
     */
    protected $taxationSystem;
    /**
     * Company's inn
     *
     * @var mixed
     */
    protected $inn;

    /**
     * orangedata constructor.
     *
     * @param $auth
     * @param $client
     */
    public function __construct($auth, Client $client)
    {
//        print_r(); die();
        $this->client = new OrangeDataClient($auth, $client);

        $this->inn = $auth['inn'];
        $this->taxationSystem = $auth['sno'];

        //ToDo: check connection
        $result = $this->client->check_connection();

        if ($result[1]['http_code'] != 200) {
            throw new \InvalidArgumentException(
                "OrangaData Auth error: " . $result[0], $result[1]['http_code']
            );
        }
    }

    /**
     * Prepare receipt data for sending
     *
     * @param $type
     * @param $data
     * @throws \Exception
     */
    private function prepareReceipt($type, $data)
    {
        $this->client->create_order([
            'id' => $data['id'],
            'type' => $type,
            'customerContact' => (!empty($data['client']['email']) ? $data['client']['email'] : $data['client']['phone']),
            'taxationSystem' => $this->taxationSystem,
            'key' => $this->inn,
            'group' => 'Main'
        ]);

        foreach ($data['items'] as $i => $val) {
            $item = [
                'quantity' => $val['quantity'],
                'price' => round(($val['sum'] / $val['quantity']), 2),
                'tax' => $this->mathVat($val['vat']),
                'text' => $val['name'],
                'paymentMethodType' => $val['payment_method'],
                'paymentSubjectType' => $val['payment_object'],
                'nomenclatureCode' => '',
                'supplierInfo' => '',
                'supplierINN' => '',
                'agentType' => '',
                'agentInfo' => '',
                'unitOfMeasurement' => '',
                'additionalAttribute' => '',
                'manufacturerCountryCode' => '',
                'customsDeclarationNumber' => '',
                'excise' => 0
            ];
            $this->client->add_position_to_order($item);
        }

        $payment = [
            'type' => 2,
            'amount' => $data['total'],
        ];

        $this->client->add_payment_to_order($payment);
    }

    /**
     * Создание чека. Параметры запроса одинаковы для все интегрированных касс.
     *
     * - id уникальный идентификатор чека
     * - time время создания чека в строковом представлении
     * - client массив данных о клиенте:
     *      - name имя
     *      - email почта
     *      - phone телефон
     * Имя и (почта или телефон) обязательны к заполнению
     * - items массив объектов сведений о товарах:
     *      - name наименование товара
     *      - price цена товара
     *      - quantity количество товара
     *      - sum сумма по товару с учетом скидки
     *      - payment_method метод расчетов
     *      - payment_object объект расчетов
     *      - vat ставка налогооблажения
     * - total общая сумма платежа
     *
     * @param array $data
     * @return array|mixed|void
     * @throws \Exception
     */
    public function saveBill($data)
    {
        // TODO: Implement saveBill() method.
        $this->prepareReceipt(1, $data);

        $result = $this->client->send_order();

        return $result;
    }

    /**
     * Создание чека возврата. Параметры запроса одинаковы для все интегрированных касс.
     *
     * - id уникальный идентификатор чека
     * - time время создания чека в строковом представлении
     * - client массив данных о клиенте:
     *      - name имя
     *      - email почта
     *      - phone телефон
     * Имя и (почта или телефон) обязательны к заполнению
     * - items массив объектов сведений о товарах:
     *      - name наименование товара
     *      - price цена товара
     *      - quantity количество товара
     *      - sum сумма по товару с учетом скидки
     *      - payment_method метод расчетов
     *      - payment_object объект расчетов
     *      - vat ставка налогооблажения
     * - total общая сумма платежа
     *
     * @param array $data
     * @return mixed|void
     * @throws \Exception
     */
    public function refundBill($data)
    {
        // TODO: Implement refundBill() method.
        $this->prepareReceipt(2, $data);

        $result = $this->client->send_order();

        return $result;
    }

    /**
     * Calculate vat
     *
     * @param $type
     * @return int
     */
    private function mathVat($type)
    {
        switch ($type) {
            case "none":
                return 6;
            case "vat0":
                return 5;
            case "vat10":
                return 2;
            case "vat110":
                return 4;
            case "vat20":
                return 1;
            case "vat120":
                return 3;
            default:
                throw new \InvalidArgumentException(
                    "Incorrect vat"
                );
        }
    }

    /**
     * Get boxes payment methods
     *
     * @return array
     */
    public static function getPaymentMethod()
    {
        return [
            1 => 'Предоплата 100%',
            2 => 'Частичная предоплата',
            3 => 'Аванс',
            4 => 'Полный расчет',
            5 => 'Частичный расчет и кредит',
            6 => 'Передача в кредит',
            7 => 'Оплата кредита'
        ];
    }

    /**
     * Get boxes payment objects
     *
     * @return array
     */
    public static function getPaymentObject()
    {
        return [
            1 => 'Товар',
            2 => 'Подакцизный товар',
            3 => 'Работа',
            4 => 'Услуга',
            5 => 'Ставка азартной игры',
            6 => 'Выигрыш азартной игры',
            7 => 'Лотерейный билет',
            8 => 'Выигрыш лотереи',
            9 => 'Предоставление РИД',
            10 => 'Платеж',
            11 => 'Агентское вознаграждение',
            12 => 'Составной предмет расчета',
            13 => 'Иной предмет расчета',
            14 => 'Имущественное право',
            15 => 'Внереализационный доход*',
            16 => 'Страховые взносы*',
            17 => 'Торговый сбор',
            18 => 'Курортный сбор',
            19 => 'Залог'
        ];
    }

    /**
     * Get boxes taxation systems
     *
     * @return array
     */
    public static function getTaxationSystem()
    {
        return [
            0 => 'Общая, ОСН',
            1 => 'Упрощенная доход, УСН доход',
            2 => 'Упрощенная доход минус расход, УСН доход - расход',
            3 => 'Единый налог на вмененный доход, ЕНВД',
            4 => 'Единый сельскохозяйственный налог, ЕСН',
            5 => 'Патентная система налогообложения, Патент'
        ];
    }

    /**
     * Get boxes vats
     *
     * @return array
     */
    public static function getVats()
    {
        return [
            'none' => 'Без НДС',
            'vat0' => 'НДС 0%',
            'vat10' => 'НДС 10%',
            'vat110' => 'Рассчетный НДС 10%',
            'vat20' => 'НДС 20%',
            'vat120' => 'Рассчетный НДС 20%'
        ];
    }
}