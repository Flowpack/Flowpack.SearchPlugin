<?php

namespace Flowpack\SearchPlugin\EelHelper;

use Neos\ContentRepository\Search\Search\QueryBuilderInterface;
use Neos\Eel\ProtectedContextAwareInterface;

class PaginateHelper implements ProtectedContextAwareInterface
{
    protected QueryBuilderInterface $query;

    protected int $itemsPerPage = 10;

    protected int $currentPage = 1;

    protected int $maximumNumberOfLinks = 99;

    protected int $numberOfPages = 1;

    protected int $displayRangeStart;

    protected int $displayRangeEnd;

    public function paginate(
        QueryBuilderInterface $query = null,
        int                   $itemsPerPage = 10,
        int                   $maximumNUmberOfLinks = 99,
        int                   $currentPage = 1
    ): array
    {
        if ($query === null) {
            return [];
        }

        $this->query = $query;
        $this->currentPage = $currentPage;
        $this->itemsPerPage = $itemsPerPage;
        $this->maximumNumberOfLinks = $maximumNUmberOfLinks;
        $this->numberOfPages = (int)ceil($this->query->count() / $itemsPerPage);

        return $this->reduceQueryResults();
    }

    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }

    private function reduceQueryResults(): array
    {
        if ($this->currentPage < 1) {
            $this->currentPage = 1;
        } elseif ($this->currentPage > $this->numberOfPages) {
            $this->currentPage = $this->numberOfPages;
        }

        $itemsPerPage = $this->itemsPerPage;
        $this->query->limit($itemsPerPage);
        if ($this->currentPage > 1) {
            $this->query->from($itemsPerPage * ($this->currentPage - 1));
        }
        $modifiedObjects = $this->query->execute();

        return [
            'results' => $modifiedObjects,
            'pagination' => $this->buildPagination(),
        ];
    }

    /**
     * If a certain number of links should be displayed, adjust before and after
     * amounts accordingly.
     */
    private function calculateDisplayRange(): void
    {
        $maximumNumberOfLinks = $this->maximumNumberOfLinks;
        if ($maximumNumberOfLinks > $this->numberOfPages) {
            $maximumNumberOfLinks = $this->numberOfPages;
        }
        $delta = (int)floor($maximumNumberOfLinks / 2);
        $this->displayRangeStart = $this->currentPage - $delta;
        $this->displayRangeEnd = $this->currentPage + $delta + ($maximumNumberOfLinks % 2 === 0 ? 1 : 0);
        if ($this->displayRangeStart < 1) {
            $this->displayRangeEnd -= $this->displayRangeStart - 1;
        }
        if ($this->displayRangeEnd > $this->numberOfPages) {
            $this->displayRangeStart -= ($this->displayRangeEnd - $this->numberOfPages);
        }
        $this->displayRangeStart = max($this->displayRangeStart, 1);
        $this->displayRangeEnd = min($this->displayRangeEnd, $this->numberOfPages);
    }

    /**
     * Returns an array with the keys "pages", "current", "numberOfPages", "nextPage" & "previousPage"
     */
    private function buildPagination(): array
    {
        $this->calculateDisplayRange();
        $pages = [];
        for ($i = $this->displayRangeStart; $i <= $this->displayRangeEnd; $i++) {
            $pages[] = ['number' => $i, 'isCurrent' => ($i === $this->currentPage)];
        }
        $pagination = [
            'pages' => $pages,
            'currentPage' => $this->currentPage,
            'numberOfPages' => $this->numberOfPages,
            'displayRangeStart' => $this->displayRangeStart,
            'displayRangeEnd' => $this->displayRangeEnd,
            'hasLessPages' => $this->displayRangeStart > 2,
            'hasMorePages' => $this->displayRangeEnd + 1 < $this->numberOfPages
        ];
        if ($this->currentPage < $this->numberOfPages) {
            $pagination['nextPage'] = $this->currentPage + 1;
        }
        if ($this->currentPage > 1) {
            $pagination['previousPage'] = $this->currentPage - 1;
        }
        return $pagination;
    }
}
