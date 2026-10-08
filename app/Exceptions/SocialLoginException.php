<?php

namespace App\Exceptions;

use RuntimeException;

/** Ошибка входа через внешний аккаунт; сообщение безопасно показывать пользователю. */
class SocialLoginException extends RuntimeException {}
