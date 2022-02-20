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

namespace Quadro\Http;

interface ResponseInterface
{
    public function getStatus(): string;
    public function getStatusText(): string;
    public function setStatusText(string $text): static;
    public function getStatusCode(): int;
    public function setStatusCode(int $code): static;
    public function getHeaders(): array;
    public function getContent(): string;
    public function setContent(mixed $content, bool $append = false): self;
    public function send(): void;
}