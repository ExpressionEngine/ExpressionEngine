<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Member;

/**
 * Member Service
 */
class Member
{
    /* Member role constants */
    const SUPERADMIN = 1;
    const BANNED = 2;
    const GUESTS = 3;
    const PENDING = 4;
    const MEMBERS = 5;
    
    /**
     * Gets array of members who can be authors
     *
     * @param string $search Optional search string to filter members by
     * @param boolean $limited Limit the list to the default 100? Use FALSE sparingly
     * @return array ID => Screen name array of authors
     */
    public function getAuthors($search = null, $limited = true)
    {
        // First, get member groups who should be in the list
        $role_settings = ee('Model')->get('RoleSetting')
            ->with('Role')
            ->filter('include_in_authorlist', 'y')
            ->filter('site_id', ee()->config->item('site_id'))
            ->all();

        $roles = $role_settings->Role;
        $role_ids = $roles->pluck('role_id');

        $member_ids = [];
        foreach ($roles as $role) {
            $member_ids = array_merge($role->getAllMembers()->pluck('member_id'), $member_ids);
        }

        // Then authors who are individually selected to appear in author list
        $authors = ee('Model')->get('Member')
            ->fields('username', 'screen_name')
            ->filter('in_authorlist', 'y');

        if ($limited) {
            $authors->limit(100);
        }

        // Then grab any members that are part of the member groups we found
        if (! empty($member_ids)) {
            $authors->orFilter('member_id', 'IN', $member_ids);
        }

        if ($search) {
            $authors->search(
                ['screen_name', 'username', 'email', 'member_id'],
                $search
            );
        }

        $authors->order('screen_name');
        $authors->order('username');

        $author_options = [];
        foreach ($authors->all() as $author) {
            $author_options[$author->getId()] = $author->getMemberName();
        }

        return $author_options;
    }

    
    /**
     * Calculate password complexity/rank
     * using metrics provided by passwordmeter.com
     * @return int
     */
    public function calculatePasswordComplexity($password)
    {
        $rank = 0;
        $length = strlen($password);
        $charsCount = [
            'upper' => 0,
            'lower' => 0,
            'number' => 0,
            'special' => 0
        ];
        $repeatChars = 0;
        $repeatIncrement = 0;
        $usedChars = [];
        $currentCharType = null;
        $prevCharType = null;
        $currentCharTypeNocase = null;
        $prevCharTypeNocase = null;
        $prePrevCharTypeNocase = null;
        $consequentCharsCount = [
            'upper' => 0,
            'lower' => 0,
            'number' => 0
        ];
        $sequenceCount = [
            'string' => 0,
            'number' => 0,
            'special' => 0
        ];
        $orderedSpecials = ['`', '!', '@', '#', '$', '%', '^', '&', '*', '(', ')'];
        $orderedNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0'];
        $orderedStrings = range('a', 'z');
        $orderedKeyboard = ['q', 'w', 'e', 'r', 't', 'y', 'u', 'i', 'o', 'p', 'a', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'z', 'x', 'c', 'v', 'b', 'n', 'm'];
        for ($i = 0; $i < $length; $i++)
        {
            if ($password[$i] >= 'A' && $password[$i] <= 'Z') {
                $currentCharType = 'upper';
                $currentCharTypeNocase = 'string';
            } elseif ($password[$i] >= 'a' && $password[$i] <= 'z') {
                $currentCharType = 'lower';
                $currentCharTypeNocase = 'string';
            } elseif ($password[$i]>= '0' && $password[$i]<= '9') {
                if ($i > 0 && $i < ($length-1)) {
                    //Middle Numbers or Symbols
                    $rank += 2;
                }
                $currentCharType = $currentCharTypeNocase = 'number';
            } else {
                if ($i > 0 && $i < ($length-1)) {
                    //Middle Numbers or Symbols
                    $rank += 2;
                }
                $currentCharType = $currentCharTypeNocase = 'special';
            }
            $charsCount[$currentCharType]++;
            $idx = strtolower($password[$i]);
            if (!isset($usedChars[$idx])) {
                $usedChars[$idx] = 0;
            }
            $usedChars[$idx]++;

            // Repeat Characters (Case Insensitive)
            $repeated = false;
            for ($j = 0; $j < $length; $j++)
            {
                if ($i != $j && strtolower($password[$i]) == strtolower($password[$j])) {
                    $repeatIncrement += abs($length/($j-$i));
                    $repeated = true;
                }
            }
            if ($repeated) {
                $repeatChars++;
            }

            if ($currentCharType == $prevCharType) {
                $consequentCharsCount[$currentCharType]++;
            }
            if ($currentCharTypeNocase == $prevCharTypeNocase && $prevCharTypeNocase == $prePrevCharTypeNocase) {
                if ($currentCharTypeNocase == 'string') {
                    $idx = array_search($password[$i], $orderedStrings);
                    if (array_search($password[$i-1], $orderedStrings) == ($idx - 1) && array_search($password[$i-2], $orderedStrings) == ($idx - 2)) {
                        $sequenceCount['string']++;
                    } else {
                        $idx = array_search($password[$i], $orderedKeyboard);
                        if (array_search($password[$i-1], $orderedKeyboard) == ($idx - 1) && array_search($password[$i-2], $orderedKeyboard) == ($idx - 2)) {
                            $sequenceCount['string']++;
                        }
                    }
                } elseif ($currentCharTypeNocase == 'number') {
                    $idx = array_search($password[$i], $orderedNumbers);
                    if (array_search($password[$i-1], $orderedNumbers) == ($idx - 1) && array_search($password[$i-2], $orderedNumbers) == ($idx - 2)) {
                        $sequenceCount['number']++;
                    }
                } elseif ($currentCharTypeNocase == 'special') {
                    $idx = array_search($password[$i], $orderedSpecials);
                    if (array_search($password[$i-1], $orderedSpecials) == ($idx - 1) && array_search($password[$i-2], $orderedSpecials) == ($idx - 2)) {
                        $sequenceCount['special']++;
                    }
                }
            }

            $prevCharType = $currentCharType;
            $prevCharTypeNocase = $currentCharTypeNocase;
            $prePrevCharTypeNocase = $prevCharTypeNocase;
        }

        // Number of Characters
        $rank += $length * 4;
        // Uppercase Letters
        $rank += ($length - $charsCount['upper']) * 2;
        // Lowercase Letters
        $rank += ($length - $charsCount['lower']) * 2;
        // Numbers
        $rank += $charsCount['number'] * 4;
        // Symbols
        $rank += $charsCount['special'] * 6;
        // Requirements
        $requirements = 0;
        foreach ($charsCount as $marker) {
            if ($marker > 0) {
                $requirements++;
            }
        }
        if ($requirements > 3) {
            if ($length >= ee()->config->item('pw_min_len')) {
                $requirements++;
                $rank += $requirements * 2;
            }
        }
        
        // Deductions
        // Letters Only
        if ($charsCount['number'] == 0 && $charsCount['special'] == 0) {
            $rank -= ($charsCount['upper'] + $charsCount['lower']);
        }
        // Numbers Only
        if ($charsCount['upper'] == 0 && $charsCount['lower'] == 0 && $charsCount['special'] == 0) {
            $rank -= $charsCount['number'];
        }
        // Repeat Characters (Case Insensitive)
        if ($repeatChars != 0) {
            $rank -= ($length > $repeatChars) ? ceil($repeatIncrement / ($length - $repeatChars)) : ceil($repeatIncrement);
        }
        // Consecutive Uppercase Letters
        $rank -= $consequentCharsCount['upper'] * 2;
        // Consecutive Lowercase Letters
        $rank -= $consequentCharsCount['lower'] * 2;
        // Consecutive Numbers
        $rank -= $consequentCharsCount['number'] * 2;
        // Sequential Letters (3+)
        $rank -= $sequenceCount['string'] * 3;
        // Sequential Numbers (3+)
        $rank -= $sequenceCount['number'] * 3;
        // Sequential Symbols (3+)
        $rank -= $sequenceCount['special'] * 3;

        return $rank;
    }
}
// EOF
