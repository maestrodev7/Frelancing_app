<?php

namespace App\Domain\Payments\Enums;

enum PaymentMethod: string
{
    case OrangeMoney = 'orange_money';
    case MtnMoney = 'mtn_money';
    case Card = 'card';

    public function kratosValue(): string
    {
        return match ($this) {
            self::OrangeMoney => 'ORANGE_MONEY',
            self::MtnMoney => 'MTN_MONEY',
            self::Card => 'CARD',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::OrangeMoney => 'Orange Money',
            self::MtnMoney => 'MTN Mobile Money',
            self::Card => 'Carte bancaire',
        };
    }

    /**
     * @return array{value: string, label: string, color: string, accent: string}
     */
    public function uiMeta(): array
    {
        return match ($this) {
            self::OrangeMoney => [
                'value' => $this->value,
                'label' => $this->label(),
                'color' => '#FF6600',
                'accent' => '#FFFFFF',
                'short' => 'Orange',
            ],
            self::MtnMoney => [
                'value' => $this->value,
                'label' => $this->label(),
                'color' => '#FFCC00',
                'accent' => '#1A1A1A',
                'short' => 'MTN',
            ],
            self::Card => [
                'value' => $this->value,
                'label' => $this->label(),
                'color' => '#1E3A5F',
                'accent' => '#FFFFFF',
                'short' => 'Carte',
            ],
        };
    }
}
