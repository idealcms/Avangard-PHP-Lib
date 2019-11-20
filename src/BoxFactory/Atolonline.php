<?php
/**
 * Copyright Bykovskiy Maxim. Avangard (c) 2019.
 */

namespace Avangard\BoxFactory;

use GuzzleHttp\Client;
use Atol\data_objects\ReceiptPosition;
use Atol\SdkException;
use Atol\services\BaseServiceRequest;
use Atol\services\CreateDocumentRequest;
use Atol\services\CreateDocumentResponse;
use Atol\services\GetTokenRequest;
use Atol\services\GetTokenResponse;
use Psr\Log\LogLevel;

/**
 * Class Atolonline
 *
 * @package Avangard\boxFactory
 */
class Atolonline implements BoxInterface
{
    /**
     * Object of Guzzle client
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;
    /**
     * Object of atol library
     *
     * @var string
     */
    protected $token;
    /**
     * Object of our company
     *
     * @var mixed
     */
    protected $company;

    /**
     * Test mode on/off
     *
     * @var bool
     */
    protected $test = false;

    /**
     * atolonline constructor.
     *
     * @param $auth
     * @param $client
     */
    public function __construct($auth, Client $client)
    {
        $this->client = $client;

        $tokenService = new GetTokenRequest($auth['login'], $auth['pass']);

        $this->company = $auth['company'];

        if(!empty($auth['testMode'])) {
            $this->test = true;
        }

        $response = $this->sendRequest($tokenService, 'GET');

        $tokenResponse = new GetTokenResponse($response);

        if (!$tokenResponse->isValid()) {
            throw new \InvalidArgumentException(
                "AtolOnline response: " . $tokenResponse->getErrorDescription(), $tokenResponse->getErrorCode()
            );
        }

        $this->token = $tokenResponse;
    }

    /**
     * Send request to atol
     *
     * @param BaseServiceRequest $service
     * @param string $method
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function sendRequest(BaseServiceRequest $service, $method = 'POST') {
        $requestParameters = $service->getParameters();
        $requestUrl = $service->getRequestUrl($this->test);

        if($method == "POST") {
            $result = $this->client->request('POST', $requestUrl, ['json' => $requestParameters]);
        } else {
            $result = $this->client->request('GET', $requestUrl);
        }

        $status = $result->getStatusCode();

        if (!in_array($status, [200, 400, 401])) {
            throw new \InvalidArgumentException(
                "Atol error. Incorrect http code: " . $status, $status
            );
        }

        $response = $result->getBody()->getContents();

        $decodedResponse = json_decode($response);
        if(empty($decodedResponse)){
            throw new \InvalidArgumentException(
                "Atol error. Empty response or not json response"
            );
        }

        return $decodedResponse;
    }

    /**
     * Prepare receipt data for sending
     *
     * @param $data
     * @param CreateDocumentRequest
     * @return CreateDocumentRequest
     * @throws SdkException
     */
    private function prepareReceipt($data, $type)
    {
        $createDocumentService = (new CreateDocumentRequest($this->token));
        if (!empty($data['client']['email'])) {
            $createDocumentService->addCustomerEmail($data['client']['email']);
        }

        $createDocumentService->addCustomerName($data['client']['name']);

        if (!empty($data['client']['phone'])) {
            $createDocumentService->addCustomerPhone($data['client']['phone']);
        }

        $createDocumentService
            ->addGroupCode($this->company['group'])
            ->addInn($this->company['inn'])
            ->addMerchantAddress($this->company['payment_address'])
            ->addSno($this->company['sno'])
            ->addOperationType($type)
            ->addPaymentType(CreateDocumentRequest::PAYMENT_TYPE_ELECTRON)
            ->addExternalId($data['id'])
            ->addTimestamp(strtotime($data['time']));

        foreach ($data['items'] as $i => $val) {
            $receiptPosition = new ReceiptPosition($val['name'], $val['price'], $val['quantity'], $val['vat'], $val['sum'], $val['payment_method'], $val['payment_object']);

            $createDocumentService->addReceiptPosition($receiptPosition);
        }

        return $createDocumentService;
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
     *
     * @param array $data
     * @return mixed|void
     */
    public function saveBill($data)
    {
        // TODO: Implement saveBill() method.
        $request = $this->prepareReceipt($data, CreateDocumentRequest::OPERATION_TYPE_SELL);

        $createDocumentResponse = new CreateDocumentResponse($this->sendRequest($request));

        if (!$createDocumentResponse->isValid()) {
            throw new \InvalidArgumentException(
                "AtolOnline response: " . $createDocumentResponse->getErrorDescription(), $createDocumentResponse->getErrorCode()
            );
        }

        return ['status' => $createDocumentResponse->status, 'uuid' => $createDocumentResponse->uuid];
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
     */
    public function refundBill($data)
    {
        // TODO: Implement refundBill() method.
        $request = $this->prepareReceipt($data, CreateDocumentRequest::OPERATION_TYPE_SELL_REFUND);

        $createDocumentResponse = new CreateDocumentResponse($this->sendRequest($request));

        if (!$createDocumentResponse->isValid()) {
            throw new \InvalidArgumentException(
                "AtolOnline response: " . $createDocumentResponse->getErrorDescription(), $createDocumentResponse->getErrorCode()
            );
        }

        return ['status' => $createDocumentResponse->status, 'uuid' => $createDocumentResponse->uuid];
    }

    /**
     * Get boxes payment methods
     *
     * @return array
     */
    public function getPaymentMethod()
    {
        return [
            'full_prepayment' => 'Предоплата 100%. Полная предварительная оплата до момента передачи предмета расчета',
            'prepayment' => 'Предоплата. Частичная предварительная оплата до момента передачи предмета расчета',
            'advance' => 'Аванс',
            'full_payment' => 'Полный расчет. Полная оплата, в том числе с учетом аванса     (предварительной оплаты) в момент передачи предмета расчета',
            'partial_payment' => 'Частичный расчет и кредит. Частичная оплата предмета расчета в момент его передачи с последующей оплатой в кредит',
            'credit' => 'Передача в кредит. Передача предмета расчета без его оплаты в момент его передачи с последующей оплатой в кредит',
            'credit_payment' => 'Оплата кредита. Оплата предмета расчета после его передачи с оплатой в кредит (оплата кредита)'
        ];
    }

    /**
     * Get boxes payment objects
     *
     * @return array
     */
    public function getPaymentObject()
    {
        return [
            'commodity' => 'товар. О реализуемом товаре, за исключением подакцизного товара (наименование и иные сведения, описывающие товар)',
            'excise' => 'подакцизный товар. О реализуемом подакцизном товаре (наименование и иные сведения, описывающие товар)',
            'job' => 'работа. О выполняемой работе (наименование и иные сведения, описывающие работу)',
            'service' => 'услуга. Об оказываемой услуге (наименование и иные сведения, описывающие услугу)',
            'gambling_bet' => 'ставка азартной игры. О приеме ставок при осуществлении деятельности по проведению азартных игр',
            'gambling_prize' => 'выигрыш азартной игры. О выплате денежных средств в виде выигрыша при осуществлении деятельности по проведению азартных игр',
            'lottery' => 'лотерейный билет. О приеме денежных средств при реализации лотерейных билетов, электронных лотерейных билетов, приеме лотерейных ставок при осуществлении деятельности по проведению лотерей',
            'lottery_prize' => 'выигрыш лотереи. О выплате денежных средств в виде выигрыша при осуществлении деятельности по проведению лотерей',
            'intellectual_activity' => 'предоставление результатов интеллектуальной деятельности. О предоставлении прав на использование результатов интеллектуальной деятельности или средств индивидуализации',
            'payment' => 'платеж. Об авансе, задатке, предоплате, кредите, взносе в счет оплаты, пени, штрафе, вознаграждении, бонусе и ином аналогичном предмете расчета',
            'agent_commission' => 'агентское вознаграждение. О вознаграждении пользователя, являющегося платежным агентом (субагентом), банковским платежным агентом (субагентом), комиссионером, поверенным или иным агентом',
            'composite' => 'составной предмет расчета. О предмете расчета, состоящем из предметов, каждому из которых может быть присвоено значение выше перечисленных признаков',
            'another' => 'иной предмет расчета. О предмете расчета, не относящемуся к выше перечисленным предметам расчета',
            'property_right' => 'имущественное право. О передаче имущественных прав',
            'non-operating_gain' => 'внереализационный доход. О внереализационном доходе',
            'insurance_premium' => 'страховые взносы. О суммах расходов, уменьшающих сумму налога (авансовых платежей) в соответствии с пунктом 3.1 статьи 346.21 Налогового кодекса Российской Федерации',
            'sales_tax' => 'торговый сбор. О суммах уплаченного торгового сбора',
            'resort_fee' => 'курортный сбор. О курортном сборе'
        ];
    }

    /**
     * Get boxes taxation systems
     *
     * @return array
     */
    public function getTaxationSystem()
    {
        return [
            'osn' => 'общая СН',
            'usn_income' => 'упрощенная СН (доходы)',
            'usn_income_outcome' => 'упрощенная СН (доходы минус расходы)',
            'envd' => 'единый налог на вмененный доход',
            'esn' => 'единый сельскохозяйственный налог',
            'patent' => 'патентная СН'
        ];
    }

    /**
     * Get boxes vats
     *
     * @return array
     */
    public function getVats()
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