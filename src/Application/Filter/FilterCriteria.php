<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Application\Filter;

final readonly class FilterCriteria
{
    public function __construct(
        public array $includeSections = [],
        public array $excludeSections = [],
        public array $includeGroups = [],
        public array $excludeGroups = [],
    ) {
    }

    public function isSectionAllowed(string $tag): bool
    {
        if (!empty($this->includeSections)) {
            return \in_array($tag, $this->includeSections, true);
        }

        return !\in_array($tag, $this->excludeSections, true);
    }

    public function isGroupAllowed(string $tag): bool
    {
        if (!empty($this->includeGroups)) {
            return \in_array($tag, $this->includeGroups, true);
        }

        return !\in_array($tag, $this->excludeGroups, true);
    }
}
