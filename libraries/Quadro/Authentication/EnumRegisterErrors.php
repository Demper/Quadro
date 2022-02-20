<?php
declare(strict_types=1);

namespace Quadro\Authentication;

enum EnumRegisterErrors
{
    case None;
    case ExceedsMaxAttempts;
    case NoCredentials;
    case CredentialsDoesNotMeetRequirements;
    case NotUnique;
    case Unexpected;

    public function getMessage(): string
    {
        return match($this) {
            EnumRegisterErrors::None => 'Ok',
            EnumRegisterErrors::ExceedsMaxAttempts => 'Exceeds maximum number of registrations for this IP',
            EnumRegisterErrors::NoCredentials => 'No credentials provided or found',
            EnumRegisterErrors::CredentialsDoesNotMeetRequirements => 'Credentials does not meet our standards',
            EnumRegisterErrors::NotUnique => 'User already exists with these credentials',
            EnumRegisterErrors::Unexpected => 'Unexpected error',
        };
    }

}