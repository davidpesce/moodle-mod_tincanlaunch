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

// TODO: should this be an implementation of an interface?
abstract class Document
{
    use ArraySetterTrait;

    protected $id;
    protected $contentType;
    protected $content;
    protected $etag;
    protected $timestamp;

    public function __construct() {
        if (func_num_args() == 1) {
            $arg = func_get_arg(0);

            $this->_fromArray($arg);
        }
    }

    public function setId($value) {
        $this->id = $value;
        return $this;
    }
    public function getId() {
        return $this->id;
    }
    public function setContentType($value) {
        $this->contentType = $value;
        return $this;
    }
    public function getContentType() {
        return $this->contentType;
    }
    public function setContent($value) {
        $this->content = $value;
        return $this;
    }
    public function getContent() {
        return $this->content;
    }
    public function setEtag($value) {
        $this->etag = $value;
        return $this;
    }
    public function getEtag() {
        return $this->etag;
    }

    public function setTimestamp($value) {
        if (isset($value)) {
            if ($value instanceof \DateTime) {
                // Use format('c') instead of format(\DateTime::ISO8601) due to bug in format(\DateTime::ISO8601) that generates an invalid timestamp.
                $value = $value->format('c');
            } else if (is_string($value)) {
                $value = $value;
            } else {
                throw new \InvalidArgumentException('type of arg1 must be string or DateTime');
            }
        }

        $this->timestamp = $value;

        return $this;
    }
    public function getTimestamp() {
        return $this->timestamp;
    }
}
