<?php

namespace App\Enums;

enum NotificationType: string
{
	case SMS = 'sms';
	case EMAIL = 'email';
	case PUSH = 'push';
}