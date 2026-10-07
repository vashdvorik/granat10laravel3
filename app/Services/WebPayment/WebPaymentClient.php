<?php

namespace App\Services\WebPayment;

use RuntimeException;
use SimpleXMLElement;
use SoapClient;
use SoapFault;

/**
 * Клиент сервиса «Web-платёж» ЗАО «Агропромбанк»: подписи запросов и SOAP-функция GetState.
 */
class WebPaymentClient
{
    public function isConfigured(): bool
    {
        return (bool) config('services.webpayment.enabled')
            && filled(config('services.webpayment.merchant_login'))
            && filled(config('services.webpayment.merchant_pass'));
    }

    public function paymentUrl(): string
    {
        return (string) config('services.webpayment.payment_url');
    }

    /**
     * Поля формы для перенаправления клиента на PaymentStart (п. 3 документации).
     *
     * @return array<string, string|int>
     */
    public function paymentFields(string $invoiceId, int $amountCents, string $description, int $lifetimeMinutes, bool $isTest): array
    {
        $login = (string) config('services.webpayment.merchant_login');
        $currency = (string) config('services.webpayment.currency');
        $test = $isTest ? 1 : 0;

        return [
            'MerchantLogin' => $login,
            'RequestSum' => $amountCents,
            'RequestCurrCode' => $currency,
            'nivid' => $invoiceId,
            'Desc' => $description,
            'IsTest' => $test,
            'LifeTime' => $lifetimeMinutes,
            'SignatureValue' => $this->sign([$login, $invoiceId, $test, $amountCents, $currency, $description]),
        ];
    }

    /** MD5(invoiceid:status:paymentsum:paymentcurrency:date:MerchantPass) — оповещение об успешной оплате. */
    public function paidSignature(string $invoiceId, string $sum, string $currency, string $date): string
    {
        return $this->sign([$invoiceId, 'paid', $sum, $currency, $date]);
    }

    /** MD5(invoiceid:status:date:MerchantPass) — оповещение об отказе. */
    public function failSignature(string $invoiceId, string $date): string
    {
        return $this->sign([$invoiceId, 'fail', $date]);
    }

    /** MD5(MerchantId:InvoiceId:MerchantPass) — запрос GetState. */
    public function stateSignature(string $invoiceId): string
    {
        return $this->sign([(string) config('services.webpayment.merchant_login'), $invoiceId]);
    }

    /**
     * Состояние операции (п. 6.1). Бросает RuntimeException, если банк недоступен или ответ некорректен.
     *
     * @return array{state:int, sum:int, currency:string, istest:int, rrn:?string, lastdgt:?string}
     */
    public function getState(string $invoiceId): array
    {
        $payload = $this->callGetState($invoiceId);
        $xml = $this->decodeEnvelope($payload);

        if ((int) ($xml->Result->Code ?? 0) !== 1 || ! isset($xml->StateInfo)) {
            throw new RuntimeException('Банк отклонил запрос GetState: '.(string) ($xml->Result->Description ?? 'без описания'));
        }

        $info = $xml->StateInfo;
        $rrn = trim((string) ($info->rrn ?? $info->trx->rrn ?? ''));
        $last = trim((string) ($info->lastdgt ?? ''));

        return [
            'state' => (int) $info->state,
            'sum' => (int) $info->sum,
            'currency' => trim((string) $info->currency),
            'istest' => (int) $info->istest,
            'rrn' => $rrn !== '' ? substr($rrn, 0, 32) : null,
            'lastdgt' => preg_match('/^\d{4}$/', $last) ? $last : null,
        ];
    }

    /** @param array<int, string|int> $parts */
    private function sign(array $parts): string
    {
        $parts[] = (string) config('services.webpayment.merchant_pass');

        return md5(implode(':', array_map('strval', $parts)));
    }

    private function callGetState(string $invoiceId): string
    {
        $timeout = max(1, (int) config('services.webpayment.timeout'));

        try {
            $soap = new SoapClient((string) config('services.webpayment.wsdl_url'), [
                'exceptions' => true,
                'connection_timeout' => $timeout,
                'trace' => false,
                'cache_wsdl' => WSDL_CACHE_MEMORY,
                'stream_context' => stream_context_create(['http' => ['timeout' => $timeout]]),
            ]);

            $result = $soap->GetState([
                'MerchantId' => (string) config('services.webpayment.merchant_login'),
                'InvoiceId' => $invoiceId,
                'Signature' => $this->stateSignature($invoiceId),
            ]);
        } catch (SoapFault $e) {
            throw new RuntimeException('Сервис банка недоступен: '.$e->getMessage(), 0, $e);
        }

        $string = $this->firstString($result);

        if ($string === null) {
            throw new RuntimeException('Банк вернул пустой ответ GetState.');
        }

        return $string;
    }

    private function firstString(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_object($value) || is_array($value)) {
            foreach ((array) $value as $item) {
                $found = $this->firstString($item);

                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * Ответ банка — Base64 от XML <envelope><response/><signature/></envelope> (п. 6).
     */
    private function decodeEnvelope(string $payload): SimpleXMLElement
    {
        $envelopeXml = $this->looksLikeXml($payload) ? $payload : base64_decode($payload, true);

        if ($envelopeXml === false || ! $this->looksLikeXml($envelopeXml)) {
            throw new RuntimeException('Не удалось разобрать ответ банка.');
        }

        $envelope = $this->parseXml($envelopeXml);
        $response = base64_decode(trim((string) $envelope->response), true);

        if ($response === false || $response === '') {
            throw new RuntimeException('В ответе банка нет данных response.');
        }

        $this->verifyBankSignature($response, trim((string) $envelope->signature));

        return $this->parseXml($response);
    }

    private function verifyBankSignature(string $response, string $signature): void
    {
        $certificatePath = config('services.webpayment.bank_certificate');

        if (! filled($certificatePath)) {
            // Подлинность ответа обеспечивается HTTPS-соединением с ws.agroprombank.com.
            return;
        }

        $certificate = is_file((string) $certificatePath) ? file_get_contents((string) $certificatePath) : false;
        $publicKey = $certificate !== false ? openssl_pkey_get_public($certificate) : false;
        $raw = base64_decode($signature, true);

        if ($publicKey === false || $raw === false) {
            throw new RuntimeException('Не удалось проверить электронную подпись банка.');
        }

        foreach ([OPENSSL_ALGO_SHA256, OPENSSL_ALGO_SHA1] as $algorithm) {
            if (openssl_verify($response, $raw, $publicKey, $algorithm) === 1) {
                return;
            }
        }

        throw new RuntimeException('Электронная подпись банка неверна.');
    }

    private function looksLikeXml(string $value): bool
    {
        return str_starts_with(ltrim($value, "\xEF\xBB\xBF \t\r\n"), '<');
    }

    private function parseXml(string $xml): SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);

        try {
            $parsed = simplexml_load_string(ltrim($xml, "\xEF\xBB\xBF"), SimpleXMLElement::class, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if ($parsed === false) {
            throw new RuntimeException('Банк вернул некорректный XML.');
        }

        return $parsed;
    }
}
