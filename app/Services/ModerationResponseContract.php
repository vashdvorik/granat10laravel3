<?php

namespace App\Services;

/** Формат JSON-ответа, который требуется от нейросети при проверке текста и фото. */
class ModerationResponseContract
{
    public function instruction(): string
    {
        return 'Верни только JSON. '
            .'Разрешённые code в violations: '.implode(', ', $this->allowedViolationCodes()).'. '
            .'Не возвращай другие code. Все тексты для продавца пиши только на русском языке: evidence, suggestion и seller_message. '
            .'Формат: {"decision":"allow|review|block","risk_score":0..1,'
            .'"violations":[{"code":"string","confidence":0..1,"evidence":"string",'
            .'"field":"name|description|features|category|images|general","suggestion":"string|null"}],'
            .'"seller_message":"string|null"}.';
    }

    /**
     * @return array<int, string>
     */
    public function allowedViolationCodes(): array
    {
        return [
            'spam',
            'nonsense',
            'prohibited_goods',
            'prohibited_services',
            'illegal_goods',
            'illegal_services',
            'illegal_drugs',
            'weapons',
            'alcohol',
            'adult_services',
            'fraud',
            'fraud_financial_scheme',
            'documents_money',
            'profanity',
            'obscene_language',
            'offensive_language',
            'wrong_category',
            'category_mismatch',
            'prohibited_image',
            'unsafe_image',
        ];
    }
}
