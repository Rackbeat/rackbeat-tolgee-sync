<?php

namespace Rackbeat\TolgeeSync\Builders;

use Rackbeat\TolgeeSync\Requests\Request;
use Rackbeat\TolgeeSync\Exceptions\TolgeeException;

class TolgeeBuilder extends Request {

    private ?string $projectId = null;

    public function __construct(string $tolgeeAPIKey)
    {
        parent::__construct(config('tolgee.base_url'), $this->XApiKeyHeader($tolgeeAPIKey));
    }

    /** ROUTES */
    public function keys(): TolgeeBuilder
    {
        $this->addRouteParam('/keys');
        return $this;
    }

    public function languages(): TolgeeBuilder
    {
        $this->addRouteParam('/languages');
        return $this;
    }

    public function projects(): TolgeeBuilder
    {
        $this->addRouteParam('', true, false);
        return $this;
    }

    public function translations(): TolgeeBuilder
    {
        $this->addRouteParam('/translations');
        return $this;
    }

    public function history(): TolgeeBuilder
    {
        $this->addRouteParam('/history', false);
        return $this;
    }

    public function setTranslationState(string $state): TolgeeBuilder
    {
        $this->addRouteParam("/set-state/$state", false);
        return $this;
    }

    public function autoTranslate(array $languages = [], bool $useMachineTranslation = true, bool $useTranslationMemory = true): TolgeeBuilder
    {
        $this->addRouteParam('/auto-translate', false);
        $this->addQueryParam('languages', $languages);
        $this->addQueryParam('useMachineTranslation', $useMachineTranslation ? 'true' : 'false');
        $this->addQueryParam('useTranslationMemory', $useTranslationMemory ? 'true' : 'false');
        return $this;
    }

    public function comments(): TolgeeBuilder
    {
        $this->addRouteParam('/comments', false);
        return $this;
    }

    public function tags(): TolgeeBuilder
    {
        $this->addRouteParam('/tags', false);
        return $this;
    }

    public function find(string $find): TolgeeBuilder
    {
        $this->addRouteParam("/$find", false);
        return $this;
    }

    /** GETTERS AND SETTERS */

    public function setProject(string $projectId): TolgeeBuilder
    {
        $this->projectId = $projectId;
        return $this;
    }

    public function getProject(): ?string
    {
        return $this->projectId;
    }

    /** HELPER FUNCTIONS */

    private function addRouteParam(string $route, bool $refresh = true, bool $projectRequired = true)
    {
        if ($refresh) {
            $this->path = $this->projectId ? "/$this->projectId" : "";
            $this->queryParams = [];
        }

        if ($projectRequired && ! $this->getProject()) {
            throw TolgeeException::missingProject();
        }

        $this->path .= $route;
    }

    /** QUERY PARAMS */

    /**
     * Get pagination size. Max size = 2000
     */
    public function take(int $number = 500): TolgeeBuilder
    {
        $this->addQueryParam('size', $number);
        return $this;
    }

    /**
     * Get page. Pagination starts at 0, max page = total pages - 1
     */
    public function page(int $page = 1): TolgeeBuilder
    {
        $this->addQueryParam('page', $page);
        return $this;
    }

    /**
     * Filter Namespace. Namespaces in Tolgee are file names in Rackbeat
     */
    public function forNamespace(string $namespace): TolgeeBuilder
    {
        $this->addQueryParam('filterNamespace', $namespace);
        return $this;
    }

    /**
     * Filter State. States in Tolgee are TRANSLATED and REVIEWED.
     */
    public function forState(string $state): TolgeeBuilder
    {
        $this->addQueryParam('filterState', 'state,' . $state);
        return $this;
    }

    /**
     * Set sort by DESC. Default sort is ASC
     */
    public function sortDesc(): TolgeeBuilder
    {
        $this->addQueryParam('sort', 'desc');
        return $this;
    }

    /**
     * Set sort by parameter.
     */
    public function orderBy(string $param, string $direction): TolgeeBuilder
    {
        $this->addQueryParam('sort', "{$param},{$direction}");
        return $this;
    }

    /**
     * Filter translations for language. If not added, only DA and EN translations are returned
     */
    public function forLanguage(string $locale): TolgeeBuilder
    {
        $this->addQueryParam('languages', $locale);
        return $this;
    }

    /**
     * Returns the X-API-KEY
     */
    private function XApiKeyHeader(string $tolgeeAPIKey): array
    {
        return [
            'x-api-key: ' . $tolgeeAPIKey
        ];
    }
}

