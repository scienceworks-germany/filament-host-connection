<?php

namespace ScienceWorks\HostConnection\Concerns;

use ScienceWorks\HostConnection\Client\HostConnectionClient;
use ScienceWorks\HostConnection\Contracts\HostConnection;
use ScienceWorks\HostConnection\Storage\ModelHostConnection;

/**
 * Attach to a tenant / account model to give it its own host pairing. Override
 * hostConnectionColumns() if your schema uses custom column names, and
 * hostConnectionTypes() to advertise what the pairing is used for.
 *
 * This trait intentionally stays thin — all HTTP lives in HostConnectionClient,
 * all storage logic lives in ModelHostConnection. The trait is glue.
 */
trait HasHostConnection
{
    protected ?HostConnection $cachedHostConnection = null;

    public function hostConnection(): HostConnection
    {
        return $this->cachedHostConnection ??= new ModelHostConnection(
            client: app(HostConnectionClient::class),
            model: $this,
            types: $this->hostConnectionTypes(),
            columns: $this->hostConnectionColumns(),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function hostConnectionColumns(): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    protected function hostConnectionTypes(): array
    {
        return [];
    }

    public function isHostConnected(): bool
    {
        return $this->hostConnection()->isConnected();
    }
}
