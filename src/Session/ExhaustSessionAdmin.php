<?php declare(strict_types=1);

namespace Exhaust\Session;

use Seba1rx\SessionAdmin\SessionAdmin;

/**
 * SessionAdmin subclass pre-configured for the Exhaust framework.
 *
 * $appIsSpa defaults to true in the base class — correct for the SPA-first
 * architecture of Exhaust. Extend this class in the consuming app if you need
 * to inject a tab handler (seba1rx/tabmanager) or a custom session store.
 */
final class ExhaustSessionAdmin extends SessionAdmin
{
    /**
     * @param array{sessionLifetime?: int} $conf
     */
    public function __construct(array $conf = [])
    {
        if (isset($conf['sessionLifetime'])) {
            $this->sessionLifetime = $conf['sessionLifetime'];
        }
    }
}
