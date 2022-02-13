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

/**
 * These are the default headers. You may overwrite or add by creating a headers.php
 * as a sibling to the file where you initializing the Quadro API framework.
 */
if (!headers_sent()) {

    /**
     * Deny to be loaded inside a frame. If you do not want this change DENY in SAMEORIGIN
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Frame-Options
     */
    header('X-Frame-Config: DENY');

    /**
     * Control how cross side scripting
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-XSS-Protection
     */
    header('X-XSS-Protection: 1; mode=block');

    /**
     * Remove automated headers which may identify us
     *
     * X-Powered-By: Knowing how the side is build is hinting the attacker about specific vulnerabilities
     * Server      : A Web server can have vulnerabilities as wel, we dont want the attacker to know
     */
    header_remove('X-Powered-By');

    /**
     * Unfortunate replacing the Server header seems to be blocked by the webserver(s)
     * and is difficult or some times impossible to remove. We add this so you know.
     *
     * @see https://stackoverflow.com/questions/35360516/cant-remove-server-apache-header/35363645
     * @see https://stackoverflow.com/questions/35360516/cant-remove-server-apache-header/66667833#66667833
     */
    header_remove('Server');
    header('Server: Apache');

    /**
     * Browser can and will be sniffing whether the content type is wat it say it is, if
     * not the browsers are able to change this. We wil not allow this by setting
     * the following header.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Content-Type-Options
     */
    header('X-Content-Type-Config: nosniff');

    /**
     * Do not automatically open stuff
     */
    header('X-Download-Config:noopen');

    /**
     * The Strict-Transport-Security header will instruct the browser to do
     * two important things:
     *
     * 1. Load all content from your domain over HTTPS
     * 2. Refuse to connect in case of certificate errors and warnings
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Strict-Transport-Security
     */
    header('Strict-Transport-Security: max-age=15552000; includeSubDomains');

    /**
     * Content Security Policy (CSP) is an added layer of security that helps to
     * detect and mitigate certain types of attacks, including Cross
     * Site Scripting (XSS) and data injection attacks.
     *
     * If enabled, CSP has significant impact on the way browser renders pages
     * (e.g., inline JavaScript disabled by default and must be explicitly
     * allowed in policy).
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP
     */
    //header('Content-Security-Policy: default-src \'self\'  https://maxcdn.bootstrapcdn.com https://cdn.jsdelivr.net');

    /**
     * Caching depends on the environment variable, if not in production no caching
     */
    if (getenv(QUADRO_ENV_INDEX) != QUADRO_ENV_PRODUCTION) {
        header('Cache-Control: must-revalidate no-cache no-store no-transform max-age=0 s-maxage=0');
    }

    /**
     * force application json header(can overruled in Response object
     */
    header('Content-Type: text/html');
}

