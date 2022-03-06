<?php
declare(strict_types=1);

namespace Quadro\Authentication;

enum EnumAuthenticateErrors
{
    case None;
    case TokenIsEmpty;
    case TokenInvalidFormat;
    case TokenDecodeError;
    case TokenExpirationMissing;
    case TokenExpired;
    case TokenInvalid;
    case ExceedsMaxAttempts;
    case NoCredentials;
    case Failed;
    case UnknownUser;

    public function getMessage(): string
    {
        return match($this) {
            EnumAuthenticateErrors::None => 'Ok',
            EnumAuthenticateErrors::TokenIsEmpty => 'Token is empty',
            EnumAuthenticateErrors::TokenInvalidFormat => 'Token is not in the correct format',
            EnumAuthenticateErrors::TokenDecodeError => 'Unable to decode the token',
            EnumAuthenticateErrors::TokenExpirationMissing => 'Cannot find the expiration date of the token',
            EnumAuthenticateErrors::TokenExpired => 'Token is expired',
            EnumAuthenticateErrors::TokenInvalid => 'Token validation failed',
            EnumAuthenticateErrors::ExceedsMaxAttempts => 'Exceeds maximum number of logins for this IP',
            EnumAuthenticateErrors::NoCredentials => 'No credentials provided or found',
            EnumAuthenticateErrors::UnknownUser => 'Unknown User',
            EnumAuthenticateErrors::Failed => 'Failed to login with given credentials',
        };
    }
}