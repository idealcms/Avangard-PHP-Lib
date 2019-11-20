<?php
/**
 * Copyright Bykovskiy Maxim. Avangard (c) 2019.
 */

namespace Avangard\BoxFactory;

interface BoxInterface {
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
     * @return mixed|void
     */
    public function saveBill($data);

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
     */
    public function refundBill($data);

    /**
     * Get boxes payment methods
     *
     * @return array
     */
    public function getPaymentMethod();

    /**
     * Get boxes payment objects
     *
     * @return array
     */
    public function getPaymentObject();

    /**
     * Get boxes taxation systems
     *
     * @return array
     */
    public function getTaxationSystem();

    /**
     * Get boxes vats
     *
     * @return array
     */
    public function getVats();
}