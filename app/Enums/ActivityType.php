<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ActivityType: string
{
    use HasOptions;

    case Watering = 'watering';
    case Fertilizing = 'fertilizing';
    case Pruning = 'pruning';
    case PestControl = 'pest_control';
    case DiseaseTreatment = 'disease_treatment';
    case Weeding = 'weeding';
    case Inspection = 'inspection';
    case Other = 'other';

    /**
     * Get the human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Watering => __('Watering'),
            self::Fertilizing => __('Fertilizing'),
            self::Pruning => __('Pruning'),
            self::PestControl => __('Pest control'),
            self::DiseaseTreatment => __('Disease treatment'),
            self::Weeding => __('Weeding'),
            self::Inspection => __('Inspection'),
            self::Other => __('Other'),
        };
    }
}
