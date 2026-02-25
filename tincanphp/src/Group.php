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

class Group extends Agent
{
    protected $objectType = 'Group';

    protected $member;

    public function __construct() {
        call_user_func_array('parent::__construct', func_get_args());

        if (! isset($this->member)) {
            $this->setMember([]);
        }
    }

    public function asVersion($version) {
        $result = parent::asVersion($version);

        if (count($this->member) > 0) {
            $result['member'] = [];

            foreach ($this->member as $v) {
                array_push($result['member'], $v->asVersion($version));
            }
        }

        return $result;
    }

    public function compareWithSignature($fromSig) {
        //
        // if this group is identified then it is the comparison
        // of the identifier that matters
        //
        if ($this->isIdentified() || $fromSig->isIdentified()) {
            return parent::compareWithSignature($fromSig);
        }

        //
        // anonymous groups get their member list compared,
        // short circuit when they don't have the same length
        //
        if (count($this->member) !== count($fromSig->member)) {
            return ['success' => false, 'reason' => 'Comparison of member list failed: array lengths differ'];
        }

        for ($i = 0; $i < count($this->member); $i++) {
            $comparison = $this->member[$i]->compareWithSignature($fromSig->member[$i]);
            if (! $comparison['success']) {
                return ['success' => false, 'reason' => "Comparison of member $i failed: " . $comparison['reason']];
            }
        }

        return ['success' => true, 'reason' => null];
    }

    public function setMember($value) {
        foreach ($value as $k => $v) {
            if (! $v instanceof Agent) {
                $value[$k] = new Agent($v);
            }
        }

        $this->member = $value;

        return $this;
    }
    public function getMember() {
        return $this->member;
    }
    public function addMember($value) {
        if (! $value instanceof Agent) {
            $value = new Agent($value);
        }

        array_push($this->member, $value);

        return $this;
    }
}
