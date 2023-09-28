<?php

namespace MorningMedley\Database\Classes;

class Builder extends \Illuminate\Database\Query\Builder
{
    public function addWhereExistsQuery(\Illuminate\Database\Query\Builder $query, $boolean = 'and', $not = false)
    {

        $type = $not ? 'NotExists' : 'Exists';

        $this->wheres[] = compact('type', 'query', 'boolean');

        $this->addBinding($query->getBindings(), 'where');

        return $this;
    }
}
