<?php
/**
 * This file is part of the Quadro RestFull Framework which is released under WTFPL.
 * See file LICENSE.txt or go to http://www.wtfpl.net/about/ for full license details.
 *
 * There for we do not take any responsibility when used outside the Jaribio
 * environment(s).
 *
 * If you have questions please do not hesitate to ask.
 *
 * Regards,
 *
 * Rob <rob@jaribio.nl>
 *
 * @license LICENSE.txt
 */
declare(strict_types=1);

namespace Quadro;

use Quadro\Authentication\EnumAuthenticateErrors;
use Quadro\Authentication\EnumRegisterErrors;

interface AuthenticationInterface
{
    /**
     * @param array<int|string, string> $credentials
     * @return EnumAuthenticateErrors|array<string, string>
     */
    public function authenticate(array $credentials = [] ): EnumAuthenticateErrors|array;

    /**
     * @param array<int|string, string> $credentials
     * @return EnumRegisterErrors|array<string, string>
     */
    public function register(array $credentials = [] ): EnumRegisterErrors|array;

    /**
     * @param int|string $identifier
     * @return bool|array<string, string>
     */
    public function getUserData(int|string $identifier): bool|array;

    /**
     * @param array<string, string> $userData
     * @return bool|array<string, string>
     */
    public function setUserData(array $userData): bool|array;


}