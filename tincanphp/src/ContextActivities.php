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

class ContextActivities implements ComparableInterface, VersionableInterface {
    use ArraySetterTrait;
    use FromJSONTrait;
    use AsVersionTrait;
    use SignatureComparisonTrait;

    protected $category = [];
    protected $parent = [];
    protected $grouping = [];
    protected $other = [];

    public function __construct() {
        if (func_num_args() == 1) {
            $arg = func_get_arg(0);

            $this->_fromArray($arg);
        }
    }

    private function _listSetter($prop, $value) {
        if (is_array($value)) {
            if (isset($value['id'])) {
                array_push($this->$prop, new Activity($value));
            } else {
                foreach ($value as $k => $v) {
                    if (! $value[$k] instanceof Activity) {
                        $value[$k] = new Activity($value[$k]);
                    }
                }
                $this->$prop = $value;
            }
        } else if ($value instanceof Activity) {
            array_push($this->$prop, $value);
        } else {
            throw new \InvalidArgumentException('type of arg1 must be Activity, array of Activity properties, or array of Activity/array of Activity properties');
        }
        return $this;
    }

    public function setCategory($value) {
        return $this->_listSetter('category', $value);
    }
    public function getCategory() {
        return $this->category;
    }
    public function setParent($value) {
        return $this->_listSetter('parent', $value);
    }
    public function getParent() {
        return $this->parent;
    }
    public function setGrouping($value) {
        return $this->_listSetter('grouping', $value);
    }
    public function getGrouping() {
        return $this->grouping;
    }
    public function setOther($value) {
        return $this->_listSetter('other', $value);
    }
    public function getOther() {
        return $this->other;
    }
}
