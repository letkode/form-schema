<?php

declare(strict_types=1);

namespace Letkode\FormSchema\Application\Filter;

use Letkode\FormSchema\Domain\Entity\FormGroup;
use Letkode\FormSchema\Domain\Entity\FormSection;

final readonly class StructureFilter
{
    public function __construct(private FilterCriteria $criteria)
    {
    }

    public function filterSections(iterable $sections): array
    {
        $result = [];
        foreach ($sections as $section) {
            if ($section instanceof FormSection && $this->criteria->isSectionAllowed($section->tag)) {
                $result[] = $section;
            }
        }

        return $result;
    }

    public function filterGroups(iterable $groups): array
    {
        $result = [];
        foreach ($groups as $group) {
            if ($group instanceof FormGroup && $this->criteria->isGroupAllowed($group->tag)) {
                $result[] = $group;
            }
        }

        return $result;
    }
}
