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

use Quadro\Response\EnumLinkRelations;

interface ResponseInterface
{
    /**
     * @return string
     */
    public function getStatus(): string;

    /**
     * @return string
     */
    public function getStatusText(): string;

    /**
     * @param string $text
     * @return $this
     */
    public function setStatusText(string $text): static;

    /**
     * @return int
     */
    public function getStatusCode(): int;

    /**
     * @param int $code
     * @return $this
     */
    public function setStatusCode(int $code): static;

    /**
     * @return array<int, string>
     */
    public function getHeaders(): array;

    /**
     * @param string $header
     * @param bool $replace
     * @param int $response_code
     * @return void
     */
    public function setHeader(string $header, bool $replace = true, int $response_code = 0): void;

    /**
     * @return string
     */
    public function getContent(): string;

    /**
     * @param mixed $content
     * @param bool $append
     * @return static
     */
    public function setContent(mixed $content, bool $append = false): static;

    /**
     * @param EnumLinkRelations $rel
     * @param string $href
     * @param string $method
     * @param string $type
     * @return static
     */
    public function addLink(EnumLinkRelations $rel, string $href, string $method = 'GET', string $type = 'application/json'): static;

    /**
     * @param string $message
     * @param int|string|null $index
     * @return static
     */
    public function addMessage(string $message, int|string|null $index = null): static;

    /**
     * @return array<int, array<string, string>>
     */
    public function getMessages(): array;

    /**
     * @param array<int|string, string> $messages
     * @return static
     */
    public function setMessages(array $messages = []): static;

    /**
     * @return array<int, array<string, string>>
     */
    public function getLinks(): array;

    /**
     * @return void
     */
    public function send(): void;
}