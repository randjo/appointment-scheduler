<?php

namespace App\Enums;

enum NotificationType: string
{
	case SMS = 'SMS';
	case EMAIL = 'Email';
}