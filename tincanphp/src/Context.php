<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.
/*
    Copyright 2014 Rustici Software

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
*/
namespace TinCan;

use InvalidArgumentException;

class Context implements ComparableInterface, VersionableInterface {
    use ArraySetterTrait;
    use FromJSONTrait;
    use AsVersionTrait;
    use SignatureComparisonTrait;

    protected $registration;
    protected $instructor;
    protected $team;
    protected $contextActivities;
    protected $revision;
    protected $platform;
    protected $language;
    protected $statement;
    protected $extensions;

    public function __construct() {
        if (func_num_args() == 1) {
            $arg = func_get_arg(0);

            $this->_fromArray($arg);
        }

        foreach (
            [
                'contextActivities',
                'extensions',
            ] as $k
        ) {
            $method = 'set' . ucfirst($k);

            if (! isset($this->$k)) {
                $this->$method([]);
            }
        }
    }

    public function setRegistration($value) {
        if (isset($value) && ! preg_match(Util::UUID_REGEX, $value)) {
            throw new InvalidArgumentException('arg1 must be a UUID');
        }
        $this->registration = $value;
        return $this;
    }
    public function getRegistration() {
        return $this->registration;
    }

    public function setInstructor($value) {
        if (! ($value instanceof Agent || $value instanceof Group) && is_array($value)) {
            if (isset($value['objectType']) && $value['objectType'] === "Group") {
                $value = new Group($value);
            } else {
                $value = new Agent($value);
            }
        }

        $this->instructor = $value;

        return $this;
    }
    public function getInstructor() {
        return $this->instructor;
    }

    public function setTeam($value) {
        if (! $value instanceof Group && is_array($value)) {
            $value = new Group($value);
        }

        $this->team = $value;

        return $this;
    }
    public function getTeam() {
        return $this->team;
    }

    public function setContextActivities($value) {
        if (! $value instanceof ContextActivities) {
            $value = new ContextActivities($value);
        }

        $this->contextActivities = $value;

        return $this;
    }
    public function getContextActivities() {
        return $this->contextActivities;
    }

    public function setRevision($value) {
        $this->revision = $value;
        return $this;
    }
    public function getRevision() {
        return $this->revision;
    }
    public function setPlatform($value) {
        $this->platform = $value;
        return $this;
    }
    public function getPlatform() {
        return $this->platform;
    }
    public function setLanguage($value) {
        $this->language = $value;
        return $this;
    }
    public function getLanguage() {
        return $this->language;
    }

    public function setStatement($value) {
        if (! $value instanceof StatementRef && is_array($value)) {
            $value = new StatementRef($value);
        }

        $this->statement = $value;

        return $this;
    }
    public function getStatement() {
        return $this->statement;
    }

    public function setExtensions($value) {
        if (! $value instanceof Extensions) {
            $value = new Extensions($value);
        }

        $this->extensions = $value;

        return $this;
    }
    public function getExtensions() {
        return $this->extensions;
    }
}
