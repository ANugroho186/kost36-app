<?php

namespace App\Notifications;

use App\Enums\ReminderType;
use Carbon\CarbonImmutable;

/**
 * Pengingat harian dari scheduler (lihat SendDueReminders). Hanya nomor kamar dan tanggal,
 * tanpa nominal: sistem tidak menyimpan harga sewa.
 */
class RentReminder extends PortalNotification
{
    /**
     * @param  string  $date  Tanggal acuan (Y-m-d): paid_until, extended_until, atau vacate_by.
     */
    public function __construct(
        private ReminderType $type,
        private string $roomNumber,
        private string $date,
        private int $tenancyId,
    ) {}

    protected function message(): string
    {
        $replace = [
            'room' => $this->roomNumber,
            'date' => CarbonImmutable::parse($this->date)->translatedFormat('j F Y'),
        ];

        return match ($this->type) {
            ReminderType::DueIn3Days => __('Rent for room :room is due on :date.', $replace),
            ReminderType::DueToday => __('Rent for room :room is due today (:date).', $replace),
            ReminderType::GraceDay1, ReminderType::GraceDay4, ReminderType::GraceDay7 => __('Rent for room :room was due on :date. Please pay as soon as possible.', $replace),
            ReminderType::DecisionNeeded => __('Room :room has not paid since :date. Please decide whether to extend or deactivate.', $replace),
            ReminderType::ExtensionLastDay => __('The payment extension for room :room ends today (:date).', $replace),
            ReminderType::ExtensionEnded => __('The payment extension for room :room ended on :date. Please decide again.', $replace),
            ReminderType::VacateDay => __('Room :room should be vacated today (:date). Please confirm once it is empty.', $replace),
        };
    }

    protected function url(): string
    {
        return $this->type->isForOwner()
            ? route('owner.tenancies.show', $this->tenancyId, absolute: false)
            : route('dashboard', absolute: false);
    }
}
