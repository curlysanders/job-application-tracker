<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\User;

enum PreferredTransportMode: string
{
    case Car = 'CAR';
    case PublicTransport = 'PUBLIC_TRANSPORT';
    case Bike = 'BIKE';
    case Walking = 'WALKING';

    public function label(): string
    {
        return match ($this) {
            self::Car => 'Car',
            self::PublicTransport => 'Public transport',
            self::Bike => 'Bike',
            self::Walking => 'Walking',
        };
    }
}
