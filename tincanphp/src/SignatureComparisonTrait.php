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
    Copyright 2015 Rustici Software

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

/**
 * Basic implementation of part of the ComparableInterface
 */
trait SignatureComparisonTrait
{
    /**
     * Compares the instance with a provided instance for determining
     * whether an object received in a signature is a meaningful match
     *
     * @param mixed $fromSig
     * @return array
     */
    public function compareWithSignature($fromSig) {
        $skip = property_exists($this, 'signatureSkipProperties') ? self::$signatureSkipProperties : [];

        foreach (get_object_vars($this) as $property => $value) {
            //
            // skip properties that start with an underscore to allow
            // storing information that isn't included in statement
            // structure etc. (see Attachment.content for example)
            //
            // also allow a class to specify a list of additional
            // properties that should not be included in verification
            //
            if (strpos($property, '_') === 0 || $property === 'objectType' || in_array($property, $skip)) {
                continue;
            }

            $result = self::doMatch($value, $fromSig->$property, $property);
            if (! $result['success']) {
                return $result;
            }
        }

        return [
            'success' => true,
            'reason' => null,
        ];
    }

    private static function doMatch($a, $b, $description) {
        $result = [
            'success' => false,
            'reason' => null,
        ];
        if ((isset($a) && ! isset($b)) || (isset($b) && ! isset($a))) {
            $result['reason'] = "Comparison of $description failed: value not present in this or signature";
            return $result;
        }

        if (is_object($a) && ! ($b instanceof $a)) {
            $result['reason'] = "Comparison of $description failed: not a " . get_class($a) . " value";
            return $result;
        }

        if ($a instanceof ComparableInterface) {
            $comparison = $a->compareWithSignature($b);
            if (! $comparison['success']) {
                $result['reason'] = "Comparison of $description failed: " . $comparison['reason'];
                return $result;
            }
        } else {
            if (is_array($a)) {
                if (! is_array($b)) {
                    $result['reason'] = "Comparison of $description failed: not an array in signature";
                    return $result;
                }

                if (count($a) !== count($b)) {
                    $result['reason'] = "Comparison of $description failed: array lengths differ";
                    return $result;
                }

                for ($i = 0; $i < count($a); $i++) {
                    $comparison = self::doMatch($a[$i], $b[$i], $description . "[$i]");
                    if (! $comparison['success']) {
                        return $comparison;
                    }
                }
            } else {
                if ($a != $b) {
                    $result['reason'] = "Comparison of $description failed: value is not the same";
                    return $result;
                }
            }
        }

        $result['success'] = true;
        return $result;
    }
}
