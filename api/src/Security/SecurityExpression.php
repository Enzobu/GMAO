<?php

namespace App\Security;

final class SecurityExpression
{
    public const ROLE_USER = "is_granted('ROLE_USER')";
    public const ROLE_ADMIN = "is_granted('ROLE_ADMIN')";

    public const ADMIN_OR_VEHICLE_OWNER = "is_granted('ROLE_ADMIN') or object.getVehicle().getUser() == user";
}
