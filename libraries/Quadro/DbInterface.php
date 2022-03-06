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

interface DbInterface
{
    /**
     * @param string|null $savepoint
     * @return bool
     */
    public function begin(string $savepoint = null): bool;

    /**
     * @param string|null $savepoint
     * @return bool
     */
    public function commit(string $savepoint = null): bool;

    /**
     * @param string|null $savepoint
     * @return bool
     */
    public function rollBack(string $savepoint = null): bool;

    /**
     * @param string $query
     * @param array<string, mixed>|null $params
     * @param bool $verbose
     * @return int
     */
    public function execute(string $query, array $params = null, bool $verbose = false): int;
}